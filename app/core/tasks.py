from __future__ import annotations

import asyncio
import logging
from dataclasses import dataclass
from datetime import datetime, timedelta, timezone
from typing import Callable, Dict, Iterable, List, Optional, Tuple, Union
from zoneinfo import ZoneInfo, ZoneInfoNotFoundError

from telethon import TelegramClient
from telethon.errors import (
    ChatWriteForbiddenError,
    FloodWaitError,
    PeerFloodError,
    UserAlreadyParticipantError,
    UserIdInvalidError,
    UserNotParticipantError,
    UserPrivacyRestrictedError,
    UsersTooMuchError,
)
from telethon.errors.rpcbaseerrors import BadRequestError
from telethon.tl import functions, types
from telethon.tl.types import ChatBannedRights
from telethon.tl.functions.users import GetFullUserRequest

from app.core.entity_utils import normalize_entity
from app.core.settings import AppSettings
from app.data.group_storage import GroupStorage, StoredGroup
from app.data.user_storage import StoredUser, UserStorage
from app.i18n.strings import translator

logger = logging.getLogger(__name__)


@dataclass
class ProgressUpdate:
    session_name: str
    processed: int
    total: int
    user: Optional[StoredUser] = None
    status: Optional[str] = None
    group: Optional[StoredGroup] = None


@dataclass
class DMProfile:
    dm_status: str
    dm_score: Optional[int]
    dm_score_label: Optional[str]


def _label_for_dm_score(score: int, is_bot: bool) -> str:
    if is_bot:
        return "status.dm_score_bot"
    if score <= 20:
        return "status.dm_score_closed_like"
    if score <= 40:
        return "status.dm_score_uncertain"
    if score <= 60:
        return "status.dm_score_medium"
    if score <= 80:
        return "status.dm_score_maybe_open"
    return "status.dm_score_likely_open"


async def _fetch_full_user(client: TelegramClient, user: types.User) -> Optional[types.users.UserFull]:
    try:
        return await client(GetFullUserRequest(user))
    except FloodWaitError as exc:  # pragma: no cover - network behavior
        await asyncio.sleep(int(exc.seconds) + 1)
    except Exception:
        return None
    return None


def _predict_dm_score(user: types.User, full: Optional[types.users.UserFull]) -> tuple[int, str]:
    info_score = 0
    if getattr(user, "username", None):
        info_score += 30
    if getattr(user, "phone", None):
        info_score += 25
    if getattr(user, "premium", False):
        info_score += 15
    if getattr(user, "photo", None):
        info_score += 10
    about = None
    common = 0
    if full and getattr(full, "full_user", None):
        about = getattr(full.full_user, "about", None)
        common = getattr(full.full_user, "common_chats_count", 0) or 0
    if about:
        info_score += 10
    if common >= 5:
        info_score += 10
    elif 1 <= common <= 4:
        info_score += 5

    status = getattr(user, "status", None)
    if isinstance(status, types.UserStatusOnline):
        info_score += 20
    elif isinstance(status, types.UserStatusRecently):
        info_score += 20
    elif isinstance(status, types.UserStatusLastWeek):
        info_score += 10
    elif isinstance(status, types.UserStatusLastMonth):
        info_score += 5
    elif isinstance(status, types.UserStatusEmpty):
        info_score -= 20

    info_score = max(0, min(info_score, 100))
    label = _label_for_dm_score(info_score, bool(getattr(user, "bot", False)))
    return info_score, label


async def build_dm_profile(client: TelegramClient, user: types.User, access_hash: Optional[int]) -> DMProfile:
    closed = "status.dm_closed"
    if getattr(user, "bot", False):
        return DMProfile(dm_status=closed, dm_score=0, dm_score_label=_label_for_dm_score(0, True))
    if getattr(user, "deleted", False):
        return DMProfile(dm_status=closed, dm_score=0, dm_score_label=_label_for_dm_score(0, False))
    if access_hash is None:
        return DMProfile(dm_status=closed, dm_score=0, dm_score_label=_label_for_dm_score(0, False))
    if not getattr(user, "username", None) and not getattr(user, "phone", None):
        return DMProfile(dm_status=closed, dm_score=0, dm_score_label=_label_for_dm_score(0, False))
    if getattr(user, "restricted", False):
        return DMProfile(dm_status=closed, dm_score=0, dm_score_label=_label_for_dm_score(0, False))

    full = await _fetch_full_user(client, user)
    score, label = _predict_dm_score(user, full)
    return DMProfile(dm_status="status.dm_open", dm_score=score, dm_score_label=label)


class TaskCancelled(Exception):
    pass


class SessionTask:
    def __init__(
        self,
        session_name: str,
        client: TelegramClient,
        settings: AppSettings,
        storage: Union[UserStorage, GroupStorage],
        result_storage: Optional[UserStorage] = None,
        flood_tracker: Optional[Dict[str, datetime]] = None,
        flood_key: Optional[str] = None,
    ) -> None:
        self.session_name = session_name
        self.client = client
        self.settings = settings
        self.storage = storage
        self.result_storage = result_storage
        self._cancel_event = asyncio.Event()
        self._processed = 0
        self._total = 0
        self._timezone_name = settings.timezone
        self._timezone_obj: Optional[timezone] = None
        self._flood_tracker = flood_tracker
        self._flood_key = flood_key

    def cancel(self) -> None:
        self._cancel_event.set()

    async def _check_cancelled(self) -> None:
        if self._cancel_event.is_set():
            raise TaskCancelled()

    def build_progress(
        self,
        total: Optional[int] = None,
        user: Optional[StoredUser] = None,
        status: Optional[str] = None,
        group: Optional[StoredGroup] = None,
    ) -> ProgressUpdate:
        self._processed += 1
        if total and total > 0:
            self._total = total
        elif not self._total:
            self._total = self._processed
        processed_value = self._processed
        if self._total:
            processed_value = min(self._processed, self._total)
        return ProgressUpdate(
            session_name=self.session_name,
            processed=processed_value,
            total=self._total,
            user=user,
            status=status,
            group=group,
        )

    async def _throttle(self, interval: float) -> None:
        await asyncio.sleep(max(interval, 0.1))

    def _render_message(self, template: Optional[str], user: StoredUser) -> str:
        if not template:
            return ""
        replacements = {
            "user_name": user.username or "",
            "first_name": user.first_name or "",
            "last_name": user.last_name or "",
        }
        rendered = template
        for key, value in replacements.items():
            rendered = rendered.replace(f"{{{key}}}", value)
        return rendered

    def _emit_inline_status(self, progress_callback: Callable[[ProgressUpdate], None], message: str) -> None:
        total = self._total or self._processed or 1
        progress_callback(
            ProgressUpdate(
                session_name=self.session_name,
                processed=self._processed,
                total=total,
                user=None,
                status=message,
            )
        )

    async def scan_members(
        self,
        entity: str,
        limit: Optional[int],
        active_within: Optional[timedelta],
        progress_callback: Callable[[ProgressUpdate], None],
        persist: bool,
        status_callback: Optional[Callable[[str], None]] = None,
        include_no_username: bool = True,
        offset: int = 0,
    ) -> None:
        entity = normalize_entity(entity)
        await self._check_cancelled()
        status_cb = status_callback or (lambda _: None)
        status_cb("status.running")
        total = limit or 0
        processed = 0
        fetch_limit = None
        if limit is not None and limit > 0:
            fetch_limit = limit + offset
        try:
            iterator = self.client.iter_participants(entity, limit=fetch_limit)
        except ValueError as exc:
            raise ValueError(translator.translate("log.member_target_invalid")) from exc
        skipped = 0
        seen_ids: set[int] = set()
        while True:
            await self._check_cancelled()
            try:
                participant = await iterator.__anext__()
            except StopAsyncIteration:
                break
            except FloodWaitError as exc:
                await self._handle_flood_wait(exc.seconds, status_cb)
                await self._throttle(self.settings.rate_limit.scan_interval)
                continue
            if offset and skipped < offset:
                skipped += 1
                continue
            if participant.id in seen_ids:
                continue
            try:
                user = await self.client.get_entity(participant.id)
            except FloodWaitError as exc:
                await self._handle_flood_wait(exc.seconds, status_cb)
                await self._throttle(self.settings.rate_limit.scan_interval)
                continue
            if getattr(user, "bot", False):
                continue
            if not include_no_username and not getattr(user, "username", None):
                continue
            seen_ids.add(participant.id)
            last_seen = None
            if isinstance(user.status, types.UserStatusOffline):
                was_online = user.status.was_online
                if isinstance(was_online, datetime):
                    last_seen = was_online.astimezone(timezone.utc)
                else:
                    last_seen = datetime.fromtimestamp(was_online, tz=timezone.utc)
            elif isinstance(user.status, (types.UserStatusOnline, types.UserStatusRecently)):
                last_seen = datetime.now(tz=timezone.utc)
            elif isinstance(user.status, types.UserStatusLastMonth):
                last_seen = datetime.now(tz=timezone.utc) - timedelta(days=30)
            elif isinstance(user.status, types.UserStatusLastWeek):
                last_seen = datetime.now(tz=timezone.utc) - timedelta(days=7)

            if active_within:
                if not last_seen:
                    continue
                now_utc = datetime.now(tz=timezone.utc)
                if last_seen < now_utc - active_within:
                    continue

            access_hash = getattr(user, "access_hash", None)
            input_entity: Optional[types.TypeInputPeer] = None
            if access_hash is None:
                try:
                    input_entity = await self.client.get_input_entity(types.PeerUser(user.id))
                except FloodWaitError as exc:
                    await self._handle_flood_wait(exc.seconds, status_cb)
                    await self._throttle(self.settings.rate_limit.scan_interval)
                    continue
                except (TypeError, ValueError):
                    try:
                        input_entity = await self.client.get_input_entity(user.id)
                    except (TypeError, ValueError):
                        input_entity = None
            if isinstance(input_entity, (types.InputPeerUser, types.InputUser)):
                access_hash = input_entity.access_hash

            dm_profile = await build_dm_profile(self.client, user, access_hash)
            stored = StoredUser(
                user_id=user.id,
                username=user.username,
                phone=user.phone,
                access_hash=access_hash,
                first_name=user.first_name,
                last_name=user.last_name,
                last_seen=None,
                status=type(user.status).__name__ if user.status else None,
                source=str(entity),
                is_bot=bool(getattr(user, "bot", False)),
                last_seen_utc=UserStorage.to_iso(last_seen),
                last_message=None,
                dm_status=dm_profile.dm_status,
                dm_score=dm_profile.dm_score,
                dm_score_label=dm_profile.dm_score_label,
            )
            stored = self.storage.prepare_user(stored)
            if persist:
                self.storage.add_users([stored])
            processed += 1
            progress_callback(self.build_progress(total or processed, stored, status="status.running"))
            if limit and processed >= limit:
                break
            await self._throttle(self.settings.rate_limit.scan_interval)
        logger.info("log.scan_finished")

    async def add_members(
        self,
        entity: str,
        users: Iterable[StoredUser],
        progress_callback: Callable[[ProgressUpdate], None],
        status_callback: Optional[Callable[[str], None]] = None,
    ) -> None:
        entity = normalize_entity(entity)
        users_list = list(users)
        total = len(users_list)
        status_cb = status_callback or (lambda _: None)
        status_cb("status.running")
        try:
            target_channel, target_chat = await self._resolve_target_peer(entity)
        except ValueError as exc:
            logger.exception("log.member_target_invalid")
            raise ValueError(translator.translate("log.member_target_invalid")) from exc
        chat_members: set[int] = set()
        if target_chat:
            chat_members = await self._preload_chat_members(target_chat, status_cb)
        for user in users_list:
            await self._check_cancelled()
            if user.is_bot:
                progress_callback(self.build_progress(total or 1, user, status="status.skipped_bot"))
                self.storage.remove_user(user.user_id)
                continue
            input_user = await self._resolve_input_user(user)
            if input_user is None:
                logger.warning("log.member_input_missing")
                progress_callback(self.build_progress(total or 1, user, status="status.error"))
                self.storage.remove_user(user.user_id)
                continue
            already_member = False
            if target_channel:
                already_member = await self._is_channel_member(target_channel, input_user, status_cb)
            elif target_chat:
                already_member = user.user_id in chat_members
            if already_member:
                progress_callback(self.build_progress(total or 1, user, status="status.already_member"))
                self.storage.remove_user(user.user_id)
                continue
            status_key = "status.error"
            try:
                if target_channel:
                    await self.client(
                        functions.channels.InviteToChannelRequest(
                            channel=target_channel,
                            users=[input_user],
                        )
                    )
                elif target_chat:
                    await self.client(
                        functions.messages.AddChatUserRequest(
                            chat_id=target_chat.chat_id,
                            user_id=input_user,
                            fwd_limit=0,
                        )
                    )
                    chat_members.add(user.user_id)
                status_key = "status.added"
                self._record_added_user(user, entity)
            except UserPrivacyRestrictedError:
                status_key = "status.error"
            except UserIdInvalidError:
                logger.warning("log.member_input_invalid")
                status_key = "status.error"
            except UserAlreadyParticipantError:
                status_key = "status.already_member"
            except FloodWaitError as exc:
                await self._handle_flood_wait(exc.seconds, status_cb)
                await self._throttle(self.settings.rate_limit.join_interval)
                continue
            except PeerFloodError:
                detail = self._peer_flood_detail()
                status_cb(detail)
                total_value = total or self._total or max(self._processed, 1)
                progress_callback(
                    ProgressUpdate(
                        session_name=self.session_name,
                        processed=self._processed,
                        total=total_value,
                        user=None,
                        status=detail,
                    )
                )
                logger.warning("%s", detail)
                break
            except ChatWriteForbiddenError:
                logger.warning("log.chat_write_forbidden")
                status_cb("status.chat_write_forbidden")
                status_key = "status.chat_write_forbidden"
            except UsersTooMuchError:
                logger.warning("log.users_too_much")
                status_cb("status.users_too_much")
                status_key = "status.users_too_much"
            except BadRequestError as exc:
                logger.warning("log.member_add_failed: %s", exc)
                status_cb("status.member_add_failed")
                status_key = "status.member_add_failed"
            await self._throttle(self.settings.rate_limit.join_interval)
            self.storage.remove_user(user.user_id)
            progress_callback(self.build_progress(total, user, status=status_key))
        logger.info("log.add_finished")

    async def fetch_active_senders(
        self,
        entity: str,
        limit: Optional[int],
        active_within: Optional[timedelta],
        progress_callback: Callable[[ProgressUpdate], None],
        persist: bool,
        status_callback: Optional[Callable[[str], None]] = None,
        include_no_username: bool = True,
        offset: int = 0,
    ) -> None:
        entity = normalize_entity(entity)
        cutoff = datetime.now(tz=timezone.utc) - active_within if active_within else None
        try:
            messages = self.client.iter_messages(entity, limit=limit, add_offset=offset)
        except ValueError as exc:
            raise ValueError(translator.translate("log.active_sender_missing_entity")) from exc
        processed = 0
        total = limit or 0
        seen_users: set[int] = set()
        status_cb = status_callback or (lambda _: None)
        status_cb("status.running")
        while True:
            await self._check_cancelled()
            try:
                message = await messages.__anext__()
            except StopAsyncIteration:
                break
            except FloodWaitError as exc:
                await self._handle_flood_wait(exc.seconds, status_cb)
                await self._throttle(self.settings.rate_limit.scan_interval)
                continue
            if cutoff and message.date:
                message_dt = message.date.replace(tzinfo=timezone.utc)
                if message_dt < cutoff:
                    break
            if not message.sender_id:
                continue
            if isinstance(message.sender_id, int):
                sender_key = message.sender_id
            elif isinstance(message.sender_id, (types.PeerUser,)):
                sender_key = getattr(message.sender_id, "user_id", None)
            elif isinstance(message.sender_id, (types.PeerChannel, types.PeerChat)):
                sender_key = getattr(message.sender_id, "channel_id", None) or getattr(message.sender_id, "chat_id", None)
            else:
                sender_key = None
            if sender_key and sender_key in seen_users:
                continue
            try:
                sender = await message.get_sender()
            except FloodWaitError as exc:
                await self._handle_flood_wait(exc.seconds, status_cb)
                await self._throttle(self.settings.rate_limit.scan_interval)
                continue
            except (TypeError, ValueError):
                continue
            if sender is None or not isinstance(sender, types.User):
                continue
            if getattr(sender, "bot", False):
                continue
            if sender.id in seen_users:
                continue
            if not include_no_username and not getattr(sender, "username", None):
                continue
            seen_users.add(sender.id)
            access_hash = getattr(sender, "access_hash", None)
            if access_hash is None:
                input_sender = getattr(message, "input_sender", None)
                if isinstance(input_sender, (types.InputPeerUser, types.InputUser)):
                    access_hash = input_sender.access_hash
            if access_hash is None:
                try:
                    input_peer = await self.client.get_input_entity(types.PeerUser(sender.id))
                except FloodWaitError as exc:
                    await self._handle_flood_wait(exc.seconds, status_cb)
                    await self._throttle(self.settings.rate_limit.scan_interval)
                    continue
                except (TypeError, ValueError):
                    try:
                        input_peer = await self.client.get_input_entity(sender.id)
                    except (TypeError, ValueError):
                        input_peer = None
                if isinstance(input_peer, (types.InputPeerUser, types.InputUser)):
                    access_hash = input_peer.access_hash
                else:
                    logger.warning("log.active_sender_missing_entity")
                    continue
            dm_profile = await build_dm_profile(self.client, sender, access_hash)
            stored = StoredUser(
                user_id=sender.id,
                username=sender.username,
                phone=getattr(sender, "phone", None),
                access_hash=access_hash,
                first_name=sender.first_name,
                last_name=sender.last_name,
                last_seen=None,
                status=type(sender.status).__name__ if getattr(sender, "status", None) else None,
                source=str(entity),
                is_bot=False,
                last_seen_utc=UserStorage.to_iso(message.date.replace(tzinfo=timezone.utc) if message.date else None),
                last_message=(message.message or "") if getattr(message, "message", None) else None,
                dm_status=dm_profile.dm_status,
                dm_score=dm_profile.dm_score,
                dm_score_label=dm_profile.dm_score_label,
            )
            stored = self.storage.prepare_user(stored)
            if persist:
                self.storage.add_users([stored])
            elif stored.access_hash and hasattr(self.storage, "has_user") and self.storage.has_user(stored.user_id):
                self.storage.update_user(stored)
            processed += 1
            progress_callback(self.build_progress(total or processed, stored, status="status.running"))
            if limit and processed >= limit:
                break
            await self._throttle(self.settings.rate_limit.scan_interval)
        logger.info("log.active_finished")

    async def search_groups(
        self,
        keyword: str,
        per_keyword: Optional[int],
        progress_callback: Callable[[ProgressUpdate], None],
        persist: bool,
        status_callback: Optional[Callable[[str], None]] = None,
        filters: Optional[Dict[str, bool]] = None,
    ) -> None:
        keywords = [part.strip() for part in keyword.replace("\n", ",").split(",") if part.strip()]
        if not keywords:
            raise ValueError(translator.translate("dialog.group_keyword_required"))
        status_cb = status_callback or (lambda _: None)
        status_cb("status.running")
        filters = filters or {}
        limit = max(min(per_keyword or 25, 100), 1)
        total_expected = limit * len(keywords)
        if total_expected <= 0:
            total_expected = len(keywords)
        seen: set[Tuple[str, Optional[int]]] = set()
        for term in keywords:
            try:
                result = await self.client(functions.contacts.SearchRequest(q=term, limit=limit))
            except FloodWaitError as exc:
                await self._handle_flood_wait(exc.seconds, status_cb)
                continue
            except Exception:
                continue
            chats = getattr(result, "chats", [])
            for chat in chats:
                await self._check_cancelled()
                if not isinstance(chat, (types.Chat, types.Channel)):
                    continue
                if not self._chat_matches_filters(chat, filters):
                    continue
                key = (chat.__class__.__name__, getattr(chat, "id", None))
                if key in seen:
                    continue
                seen.add(key)
                try:
                    record = await self._build_group_record(chat, term)
                except FloodWaitError as exc:
                    await self._handle_flood_wait(exc.seconds, status_cb)
                    continue
                except Exception:
                    continue
                if not record:
                    continue
                if persist and isinstance(self.storage, GroupStorage):
                    self.storage.add_groups([record])
                progress_callback(
                    self.build_progress(total_expected, status="status.running", group=record)
                )
                await self._throttle(self.settings.rate_limit.scan_interval)
        status_cb("status.completed")

    async def send_messages(
        self,
        users: Iterable[StoredUser],
        message_body: Optional[str],
        media: Optional[str],
        progress_callback: Callable[[ProgressUpdate], None],
        status_callback: Optional[Callable[[str], None]] = None,
    ) -> None:
        candidates = list(users)
        if not candidates:
            logger.info("log.message_no_users")
            return
        if not (message_body or media):
            raise ValueError(translator.translate("dialog.message_missing_body"))
        status_cb = status_callback or (lambda _: None)
        status_cb("status.running")
        total = len(candidates)
        for user in candidates:
            await self._check_cancelled()
            if user.is_bot:
                progress_callback(self.build_progress(total, user, status="status.skipped_bot"))
                continue
            input_user = await self._resolve_input_user(user)
            if input_user is None:
                logger.warning("log.member_input_missing")
                self._emit_inline_status(progress_callback, translator.translate("log.member_input_missing"))
                progress_callback(self.build_progress(total, user, status="status.message_failed"))
                continue
            rendered_text = self._render_message(message_body, user)
            send_coro: Optional[asyncio.Future] = None
            status_key = "status.message_failed"
            try:
                if media:
                    caption = rendered_text or None
                    send_coro = self.client.send_file(input_user, media, caption=caption)
                else:
                    if not rendered_text:
                        raise ValueError(translator.translate("dialog.message_missing_body"))
                    send_coro = self.client.send_message(input_user, rendered_text, link_preview=False)
                await send_coro
                status_key = "status.message_sent"
            except FloodWaitError as exc:
                await self._handle_flood_wait(exc.seconds, status_cb)
                continue
            except PeerFloodError:
                detail = self._peer_flood_detail()
                status_cb(detail)
                self._emit_inline_status(progress_callback, detail)
                break
            except ChatWriteForbiddenError:
                status_key = "status.chat_write_forbidden"
                self._emit_inline_status(progress_callback, translator.translate("log.chat_write_forbidden"))
            except UserPrivacyRestrictedError:
                status_key = "status.message_failed"
                self._emit_inline_status(progress_callback, translator.translate("log.message_privacy"))
            except BadRequestError as exc:
                message_text = str(exc)
                normalized = message_text.upper()
                if "PRIVACY_PREMIUM_REQUIRED" in normalized:
                    status_key = "status.message_premium_required"
                    warning = translator.translate("log.message_premium_required")
                    logger.warning(warning)
                    self._emit_inline_status(progress_callback, warning)
                elif "CHAT_SEND_PLAIN_FORBIDDEN" in normalized:
                    status_key = "status.message_plain_forbidden"
                    warning = translator.translate("log.message_plain_forbidden")
                    logger.warning(warning)
                    self._emit_inline_status(progress_callback, warning)
                else:
                    logger.warning("log.message_send_failed: %s", exc)
                    status_key = "status.message_failed"
                    self._emit_inline_status(
                        progress_callback,
                        f"{translator.translate('log.message_send_failed')}: {exc}",
                    )
            except Exception as exc:  # pragma: no cover - network/runtime failures
                logger.warning("log.message_send_failed: %s", exc)
                status_key = "status.message_failed"
                self._emit_inline_status(
                    progress_callback,
                    f"{translator.translate('log.message_send_failed')}: {exc}",
                )
            else:
                logger.info("log.message_sent")
            await self._throttle(self.settings.rate_limit.message_interval)
            progress_callback(self.build_progress(total, user, status=status_key))
        logger.info("log.message_finished")

    async def send_group_messages(
        self,
        groups: Iterable[StoredGroup],
        message_body: Optional[str],
        media: Optional[str],
        progress_callback: Callable[[ProgressUpdate], None],
        status_callback: Optional[Callable[[str], None]] = None,
    ) -> None:
        targets = [group for group in groups if group]
        if not targets:
            logger.info("log.group_message_no_targets")
            return
        if not (message_body or media):
            raise ValueError(translator.translate("dialog.message_missing_body"))
        status_cb = status_callback or (lambda _: None)
        status_cb("status.running")
        total = len(targets)
        for group in targets:
            await self._check_cancelled()
            input_entity = await self._resolve_group_entity(group)
            if input_entity is None:
                warning = translator.translate("log.group_target_invalid")
                logger.warning("log.group_target_invalid")
                self._emit_inline_status(progress_callback, warning)
                progress_callback(
                    self.build_progress(total, status="status.message_failed", group=group)
                )
                continue
            joined = await self._ensure_group_membership(input_entity, status_cb)
            if not joined:
                progress_callback(
                    self.build_progress(total, status="status.group_join_failed", group=group)
                )
                continue
            status_key = "status.message_failed"
            try:
                if media:
                    caption = message_body or None
                    await self.client.send_file(input_entity, media, caption=caption)
                else:
                    if not message_body:
                        raise ValueError(translator.translate("dialog.message_missing_body"))
                    await self.client.send_message(input_entity, message_body, link_preview=False)
                status_key = "status.message_sent"
            except FloodWaitError as exc:
                await self._handle_flood_wait(exc.seconds, status_cb)
                continue
            except PeerFloodError:
                detail = self._peer_flood_detail()
                status_cb(detail)
                self._emit_inline_status(progress_callback, detail)
                break
            except ChatWriteForbiddenError:
                warning = translator.translate("log.group_write_forbidden")
                logger.warning("log.group_write_forbidden")
                self._remove_group_record(group)
                self._emit_inline_status(progress_callback, warning)
            except UserPrivacyRestrictedError:
                warning = translator.translate("log.group_privacy_block")
                logger.warning("log.group_privacy_block")
                self._emit_inline_status(progress_callback, warning)
            except BadRequestError as exc:
                message_text = str(exc)
                normalized = message_text.upper()
                if "CHAT_SEND_PLAIN_FORBIDDEN" in normalized:
                    warning = translator.translate("log.message_plain_forbidden")
                    status_key = "status.message_plain_forbidden"
                else:
                    warning = f"{translator.translate('log.message_send_failed')}: {exc}"
                logger.warning(warning)
                self._emit_inline_status(progress_callback, warning)
            except Exception as exc:  # pragma: no cover
                warning = f"{translator.translate('log.message_send_failed')}: {exc}"
                logger.warning(warning)
                self._emit_inline_status(progress_callback, warning)
            await self._throttle(self.settings.rate_limit.message_interval)
            progress_callback(
                self.build_progress(total, status=status_key, group=group)
            )
        status_cb("status.completed")

    async def join_groups(
        self,
        groups: Iterable[StoredGroup],
        progress_callback: Callable[[ProgressUpdate], None],
        status_callback: Optional[Callable[[str], None]] = None,
    ) -> None:
        targets = [group for group in groups if group]
        if not targets:
            return
        status_cb = status_callback or (lambda _: None)
        status_cb("status.running")
        total = len(targets)
        for group in targets:
            await self._check_cancelled()
            input_entity = await self._resolve_group_entity(group)
            if input_entity is None:
                progress_callback(
                    self.build_progress(total, status="status.group_join_failed", group=group)
                )
                continue
            joined = await self._ensure_group_membership(input_entity, status_cb)
            status_key = "status.group_joined" if joined else "status.group_join_failed"
            progress_callback(self.build_progress(total, status=status_key, group=group))
            await self._throttle(self.settings.rate_limit.group_join_interval)
        status_cb("status.completed")

    async def _resolve_input_user(self, user: StoredUser) -> Optional[types.InputUser]:
        try:
            entity = await self.client.get_input_entity(types.PeerUser(user.user_id))
        except (TypeError, ValueError):
            try:
                entity = await self.client.get_input_entity(user.user_id)
            except (TypeError, ValueError):
                if user.username:
                    try:
                        entity = await self.client.get_input_entity(user.username)
                    except (TypeError, ValueError):
                        return None
                else:
                    return None
        if isinstance(entity, types.InputPeerUser):
            resolved = types.InputUser(user_id=entity.user_id, access_hash=entity.access_hash)
        elif isinstance(entity, types.InputUser):
            resolved = entity
        else:
            return None
        user.access_hash = resolved.access_hash
        if hasattr(self.storage, "has_user") and self.storage.has_user(user.user_id):
            self.storage.update_user(user)
        return resolved

    async def _build_group_record(
        self,
        chat: types.TypeChat,
        source: str,
    ) -> Optional[StoredGroup]:
        title = getattr(chat, "title", None) or getattr(chat, "username", None) or str(chat.id)
        members = getattr(chat, "participants_count", None)
        online = getattr(chat, "online_count", None)
        messages_restricted = False
        members_hidden = False
        group_type = self._detect_group_type(chat)
        if isinstance(chat, types.Channel):
            access_hash = getattr(chat, "access_hash", None)
            if access_hash is None:
                return None
            input_channel = types.InputChannel(chat.id, access_hash)
            try:
                full = await self.client(functions.channels.GetFullChannelRequest(channel=input_channel))
                full_chat = full.full_chat
                members = getattr(full_chat, "participants_count", members)
                online = getattr(full_chat, "online_count", online)
                messages_restricted = self._messages_restricted(group_type, full_chat, chat)
                members_hidden = self._members_hidden(full_chat)
            except FloodWaitError:
                raise
            except Exception:
                pass
        elif isinstance(chat, types.Chat):
            try:
                full_chat = await self.client(functions.messages.GetFullChatRequest(chat_id=chat.id))
                chat_info = full_chat.full_chat
                members = getattr(chat_info, "participants_count", members)
                online = getattr(chat_info, "online_count", online)
                messages_restricted = self._messages_restricted(group_type, chat_info, chat)
                members_hidden = self._members_hidden(chat_info)
            except FloodWaitError:
                raise
            except Exception:
                pass
        link = None
        if getattr(chat, "username", None):
            link = f"https://t.me/{chat.username}"
        record = StoredGroup(
            group_id=getattr(chat, "id", None),
            access_hash=getattr(chat, "access_hash", None),
            title=title,
            username=getattr(chat, "username", None),
            link=link,
            members=members,
            online=online,
            is_public=bool(getattr(chat, "username", None)),
            is_megagroup=bool(getattr(chat, "megagroup", False)),
            is_broadcast=bool(getattr(chat, "broadcast", False)),
            messages_restricted=messages_restricted,
            members_hidden=members_hidden,
            source=source,
        )
        return record

    def _detect_group_type(self, chat: types.TypeChat) -> str:
        if isinstance(chat, types.Channel):
            if getattr(chat, "megagroup", False):
                return "supergroup"
            if getattr(chat, "broadcast", False):
                return "channel"
        if isinstance(chat, types.Chat):
            return "group"
        return "unknown"

    def _members_hidden(self, full_chat: object) -> bool:
        hide_attr = getattr(full_chat, "hide_members", None)
        if hide_attr is True:
            return True
        flags = getattr(full_chat, "flags", 0)
        try:
            if flags & 512:
                return True
        except Exception:
            pass
        if getattr(full_chat, "participants_hidden", False):
            return True
        if getattr(full_chat, "participants_count", None) and not getattr(full_chat, "participants", None):
            return True
        return False

    def _messages_restricted(self, group_type: str, full_chat: object, chat: types.TypeChat) -> bool:
        if group_type == "channel":
            return True
        banned = getattr(full_chat, "default_banned_rights", None) or getattr(chat, "default_banned_rights", None)
        if isinstance(banned, ChatBannedRights) and getattr(banned, "send_messages", False):
            return True
        return False

    async def _resolve_group_entity(self, group: StoredGroup):
        hints: List[object] = []
        if group.group_id and group.access_hash and (group.is_megagroup or group.is_broadcast):
            hints.append(types.InputChannel(group.group_id, group.access_hash))
            hints.append(types.PeerChannel(group.group_id))
        if group.group_id and not group.is_megagroup and not group.is_broadcast:
            hints.append(types.InputPeerChat(group.group_id))
            hints.append(types.PeerChat(group.group_id))
        if group.username:
            hints.append(group.username)
            hints.append(f"https://t.me/{group.username}")
        if group.link:
            hints.append(group.link)
        for hint in hints:
            try:
                return await self.client.get_input_entity(hint)
            except (TypeError, ValueError):
                continue
        return None

    async def _ensure_group_membership(
        self,
        entity,
        status_callback: Callable[[str], None],
    ) -> bool:
        if isinstance(entity, types.InputPeerChannel):
            channel = types.InputChannel(entity.channel_id, entity.access_hash)
        elif isinstance(entity, types.InputChannel):
            channel = entity
        else:
            return True
        while True:
            try:
                await self.client(functions.channels.JoinChannelRequest(channel=channel))
                return True
            except UserAlreadyParticipantError:
                return True
            except FloodWaitError as exc:
                await self._handle_flood_wait(exc.seconds, status_callback)
            except ChatWriteForbiddenError:
                return False
            except Exception:
                return False

    def _remove_group_record(self, group: StoredGroup) -> None:
        if isinstance(self.storage, GroupStorage):
            self.storage.remove_group(group)

    @staticmethod
    def _chat_matches_filters(chat: types.TypeChat, filters: Dict[str, bool]) -> bool:
        is_channel = isinstance(chat, types.Channel) and bool(getattr(chat, "broadcast", False))
        is_supergroup = isinstance(chat, types.Channel) and bool(getattr(chat, "megagroup", False))
        is_group = isinstance(chat, types.Chat)
        filter_channel = filters.get("channel", False)
        filter_supergroup = filters.get("supergroup", False)
        filter_group = filters.get("group", False)
        type_filters = filter_channel or filter_supergroup or filter_group
        matches_type = True
        if type_filters:
            matches_type = (
                (filter_channel and is_channel)
                or (filter_supergroup and is_supergroup)
                or (filter_group and is_group)
            )
        if not matches_type:
            return False
        if filters.get("admin", False):
            rights = getattr(chat, "admin_rights", None)
            creator = bool(getattr(chat, "creator", False))
            has_admin = creator or bool(rights)
            if not has_admin:
                return False
        return True

    async def _resolve_target_peer(
        self, entity: str
    ) -> Tuple[Optional[types.InputChannel], Optional[types.InputPeerChat]]:
        input_entity = await self.client.get_input_entity(entity)
        channel: Optional[types.InputChannel] = None
        chat: Optional[types.InputPeerChat] = None
        if isinstance(input_entity, types.InputChannel):
            channel = input_entity
        elif isinstance(input_entity, types.InputPeerChannel):
            channel = types.InputChannel(input_entity.channel_id, input_entity.access_hash)
        elif isinstance(input_entity, types.InputPeerChat):
            chat = input_entity
        elif isinstance(input_entity, types.InputChat):
            chat = types.InputPeerChat(input_entity.chat_id)
        else:
            raise ValueError("log.member_target_invalid")
        return channel, chat

    async def _preload_chat_members(
        self, chat: types.InputPeerChat, status_callback: Callable[[str], None]
    ) -> set[int]:
        members: set[int] = set()
        while True:
            try:
                full_chat = await self.client(functions.messages.GetFullChatRequest(chat.chat_id))
                participants = getattr(full_chat.full_chat, "participants", None)
                if participants:
                    user_ids = [getattr(p, "user_id", None) for p in getattr(participants, "participants", [])]
                    members.update({uid for uid in user_ids if isinstance(uid, int)})
                break
            except FloodWaitError as exc:
                await self._handle_flood_wait(exc.seconds, status_callback)
            except Exception:
                break
        return members

    async def _is_channel_member(
        self,
        channel: types.InputChannel,
        user: types.InputUser,
        status_callback: Callable[[str], None],
    ) -> bool:
        while True:
            try:
                await self.client(
                    functions.channels.GetParticipantRequest(
                        channel=channel,
                        participant=user,
                    )
                )
                return True
            except UserNotParticipantError:
                return False
            except FloodWaitError as exc:
                await self._handle_flood_wait(exc.seconds, status_callback)
            except Exception:
                return False

    async def _handle_flood_wait(
        self, seconds: int, status_callback: Callable[[str], None]
    ) -> None:
        logger.warning("log.flood_wait")
        for remaining in range(int(seconds), 0, -1):
            await self._check_cancelled()
            message = f"{translator.translate('status.waiting_flood')} ({remaining}s)"
            status_callback(message)
            await asyncio.sleep(1)
        status_callback("status.running")

    def _peer_flood_detail(self) -> str:
        now_utc = datetime.now(tz=timezone.utc)
        stored_reset: Optional[datetime] = None
        if self._flood_tracker is not None and self._flood_key:
            stored_reset = self._flood_tracker.get(self._flood_key)
        if stored_reset and stored_reset > now_utc:
            reset_time = stored_reset
        else:
            cooldown_seconds = max(int(self.settings.rate_limit.join_interval * 40), 1800)
            reset_time = now_utc + timedelta(seconds=cooldown_seconds)
            if self._flood_tracker is not None and self._flood_key:
                self._flood_tracker[self._flood_key] = reset_time
        reset_display = reset_time.astimezone(self._current_timezone()).strftime("%d/%m/%Y %H:%M:%S")
        count = max(self._processed, 1)
        template = translator.translate("status.peer_flood_detail")
        timezone_label = self._timezone_name or "UTC"
        return template.format(count=count, reset=reset_display, tz=timezone_label)

    def _record_added_user(self, user: StoredUser, target: str) -> None:
        if not self.result_storage:
            return
        payload = user.to_dict()
        payload["source"] = target
        recorded = StoredUser(**payload)
        self.result_storage.add_users([recorded])

    def _current_timezone(self):
        if self._timezone_obj is not None:
            return self._timezone_obj
        try:
            self._timezone_obj = ZoneInfo(self._timezone_name)
        except (ZoneInfoNotFoundError, ModuleNotFoundError, ValueError):
            self._timezone_obj = timezone.utc
        return self._timezone_obj


class TaskOrchestrator:
    def __init__(self, settings: AppSettings) -> None:
        self.settings = settings
        self._tasks: Dict[Tuple[str, str], SessionTask] = {}
        self._peer_flood_resets: Dict[str, datetime] = {}

    def create_task(
        self,
        session_name: str,
        task_type: str,
        client: TelegramClient,
        storage: Union[UserStorage, GroupStorage],
        result_storage: Optional[UserStorage] = None,
    ) -> SessionTask:
        key = (session_name, task_type)
        flood_key = f"{session_name}:{task_type}"
        task = SessionTask(
            session_name,
            client,
            self.settings,
            storage,
            result_storage,
            flood_tracker=self._peer_flood_resets,
            flood_key=flood_key,
        )
        self._tasks[key] = task
        return task

    def complete_task(self, session_name: str, task_type: str) -> None:
        self._tasks.pop((session_name, task_type), None)

    def cancel_task(self, session_name: str, task_type: str) -> None:
        key = (session_name, task_type)
        task = self._tasks.get(key)
        if task:
            task.cancel()
            self._tasks.pop(key, None)

    def cancel_all(self) -> None:
        for task in self._tasks.values():
            task.cancel()
        self._tasks.clear()
