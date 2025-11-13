from __future__ import annotations

import argparse
import sys
from datetime import datetime, timezone
from pathlib import Path
from typing import Optional, Tuple

ROOT = Path(__file__).resolve().parents[1]
if str(ROOT) not in sys.path:
    sys.path.insert(0, str(ROOT))

from app.core.license import (  # noqa: E402
    PLAN_CHOICES,
    LicenseDuration,
    encode_license,
    machine_fingerprint,
)


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        description="Generate a license key for the Telegram management suite."
    )
    parser.add_argument(
        "--machine",
        help="Machine fingerprint to license. Leave empty to choose interactively.",
    )
    parser.add_argument(
        "--plan",
        choices=sorted(PLAN_CHOICES.keys()),
        help="Hazır lisans planı (1m, 3m, 6m)",
    )
    parser.add_argument("--years", type=int, default=0, help="Özel lisans süresi (yıl)")
    parser.add_argument("--months", type=int, default=0, help="Özel lisans süresi (ay)")
    parser.add_argument("--days", type=int, default=0, help="Özel lisans süresi (gün)")
    parser.add_argument("--label", help="Özel plan etiketi (varsayılan: 1Y 2A 3G formatı)")
    parser.add_argument(
        "--non-interactive",
        action="store_true",
        help="Plan veya süre belirtilmediğinde bile etkileşimli soru sorma.",
    )
    return parser


def _prompt_machine_id(default_id: str) -> str:
    try:
        entered = input(
            f"Makine ID'si girin (varsayılan={default_id}). Boş bırakırsanız varsayılan kullanılacak: "
        ).strip()
    except EOFError:
        return default_id
    return entered or default_id


def _prompt_int(prompt: str) -> int:
    while True:
        try:
            raw = input(f"{prompt} (boş bırakılırsa 0): ").strip()
        except EOFError:
            return 0
        if not raw:
            return 0
        if raw.isdigit():
            return int(raw)
        print("Lütfen sayısal bir değer girin.")


def _prompt_duration() -> LicenseDuration:
    while True:
        years = _prompt_int("Yıl sayısı")
        months = _prompt_int("Ay sayısı")
        days = _prompt_int("Gün sayısı")
        if years or months or days:
            return LicenseDuration(years, months, days)
        print("En az bir süre değeri girilmelidir.")


def _prompt_plan_or_duration() -> Tuple[Optional[str], Optional[LicenseDuration]]:
    available = ", ".join(sorted(PLAN_CHOICES.keys()))
    print(f"Mevcut planlar: {available}")
    while True:
        try:
            choice = input(
                "Plan kodu girin (örn. 1m) veya 'custom' yazarak özel süre belirleyin: "
            ).strip().lower()
        except EOFError:
            choice = ""
        if choice in PLAN_CHOICES:
            return choice, None
        if choice in {"", "custom", "özel", "ozel"}:
            return None, _prompt_duration()
        print("Geçersiz seçim. Plan kodlarından birini veya custom yazın.")


def _resolve_machine_id(cli_machine: Optional[str]) -> str:
    default_id = machine_fingerprint()
    if cli_machine:
        return cli_machine
    return _prompt_machine_id(default_id)


def _resolve_plan_and_duration(
    parser: argparse.ArgumentParser, args: argparse.Namespace
) -> Tuple[str, LicenseDuration, str]:
    if args.plan:
        if args.years or args.months or args.days:
            parser.error("Özel süre ile hazır plan aynı anda kullanılamaz.")
        label, duration = PLAN_CHOICES[args.plan]
        return args.plan, duration, label

    if args.years or args.months or args.days:
        duration = LicenseDuration(args.years, args.months, args.days)
        label = args.label or duration.label()
        return "custom", duration, label

    if args.non_interactive:
        parser.error("Hazır plan seçin veya yıl/ay/gün değerlerinden en az birini girin.")

    plan_choice, custom_duration = _prompt_plan_or_duration()
    if plan_choice:
        label, duration = PLAN_CHOICES[plan_choice]
        return plan_choice, duration, label

    duration = custom_duration or LicenseDuration(0, 0, 0)
    label = args.label or duration.label()
    return "custom", duration, label


def main(argv: Optional[list[str]] = None) -> None:
    parser = build_parser()
    args = parser.parse_args(argv)
    machine_id = _resolve_machine_id(args.machine)
    plan_code, duration, plan_label = _resolve_plan_and_duration(parser, args)

    if plan_code != "custom":
        key = encode_license(machine_id, plan_code)
    else:
        key = encode_license(machine_id, duration=duration, label=plan_label)

    expires_at = duration.apply(datetime.now(timezone.utc))
    print("Machine ID:", machine_id)
    print("Plan:", plan_label)
    print("Expires UTC:", expires_at.isoformat())
    print("License Key:", key)


__all__ = ["main", "build_parser"]
