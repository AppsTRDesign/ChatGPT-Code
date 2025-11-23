import random
import time
from typing import List


class YouTubeHandler:
    def __init__(self, browser_manager, log):
        self.browser_manager = browser_manager
        self.log = log
        self._route_pattern = '**/*googlevideo*'

    def attach_route_noise(self):
        def handler(route):  # noqa: ANN001
            url = route.request.url
            if 'googlevideo.com' in url:
                try:
                    jitter = random.uniform(0.05, 0.18)
                    if random.random() < 0.35:
                        jitter += random.uniform(0.12, 0.35)
                    time.sleep(jitter)
                except Exception:
                    pass
            route.continue_()

        return self.browser_manager.attach_route_handler(self._route_pattern, handler)

    def detach_route_noise(self):
        self.browser_manager.detach_route_handler(self._route_pattern)

    def simulate_youtube_media(self, page, media, actions: List[str], dwell_hint: int, personality) -> bool:
        try:
            self.attach_route_noise()
            if 'human_click' in actions:
                media.click()
            page.keyboard.press('Space')
            if 'fullscreen' in actions and random.random() < 0.4:
                page.keyboard.press('KeyF')
            if 'quality' in actions:
                q_btn = page.query_selector('button[aria-label*="quality" i], [class*="quality"]')
                if q_btn:
                    q_btn.click()
            jitter_ms = max(4000, dwell_hint * 1000)
            page.evaluate(
                "(durationMs) => {\n"
                " const video = document.querySelector('video');\n"
                " if (!video) return;\n"
                " video.play().catch(() => {});\n"
                " const timers = [];\n"
                " const bufferNoise = () => {\n"
                "   if (!video.buffered || video.buffered.length === 0) return;\n"
                "   const end = video.buffered.end(0);\n"
                "   const remaining = Math.max(0, video.duration - end);\n"
                "   if (remaining > 0 && Math.random() < 0.35) {\n"
                "     video.dispatchEvent(new Event('waiting'));\n"
                "     setTimeout(() => video.dispatchEvent(new Event('playing')), 200 + Math.random() * 400);\n"
                "   }\n"
                " };\n"
                " const chunk = () => {\n"
                "   const drift = 60 + Math.random() * 180;\n"
                "   if (Math.random() < 0.32) video.pause();\n"
                "   if (Math.random() < 0.32) setTimeout(() => video.play().catch(() => {}), 110 + Math.random() * 260);\n"
                "   if (Math.random() < 0.35) video.dispatchEvent(new Event('mousemove'));\n"
                "   if (Math.random() < 0.28) video.dispatchEvent(new Event('timeupdate'));\n"
                "   if (Math.random() < 0.2) video.dispatchEvent(new Event('progress'));\n"
                "   if (Math.random() < 0.4) bufferNoise();\n"
                "   return drift;\n"
                " };\n"
                " const chunkTimer = setInterval(chunk, 900 + Math.random() * 700);\n"
                " timers.push(chunkTimer);\n"
                " const focusTimer = setInterval(() => {\n"
                "   if (document.hidden && Math.random() < 0.4) {\n"
                "     document.dispatchEvent(new Event('visibilitychange'));\n"
                "   }\n"
                " }, 1200 + Math.random() * 800);\n"
                " timers.push(focusTimer);\n"
                " setTimeout(() => timers.forEach(clearInterval), durationMs + 600);\n"
                "}",
                jitter_ms,
            )
            end_time = time.time() + min(dwell_hint, 15)
            viewport = page.viewport_size or {'width': 1280, 'height': 720}
            while time.time() < end_time:
                page.wait_for_timeout(random.randint(320, 820))
                page.mouse.move(
                    random.randint(40, viewport['width'] - 40),
                    random.randint(60, viewport['height'] - 60),
                    steps=random.randint(9, 18),
                )
                if random.random() < 0.35:
                    page.keyboard.press(random.choice(['ArrowLeft', 'ArrowRight']))
                if 'volume' in actions and random.random() < 0.3:
                    page.keyboard.press(random.choice(['ArrowUp', 'ArrowDown']))
            return True
        except Exception as exc:
            self.log(f'YouTube oynatma simülasyonu atlandı: {exc}')
            return False
        finally:
            self.detach_route_noise()

    def search_and_open(self, page, keyword: str, video_link: str, search_first: bool) -> tuple[int, bool, int]:
        visited = 1
        found = False
        start = time.monotonic()
        trimmed = ''
        if video_link:
            trimmed = video_link.replace('https://www.youtube.com', '').replace('http://www.youtube.com', '')
        if search_first and keyword:
            page.goto('https://www.youtube.com/', wait_until='domcontentloaded')
            search_box = page.query_selector('input.ytSearchboxComponentInput, input#search')
            if search_box:
                search_box.click()
                for ch in keyword:
                    search_box.type(ch, delay=random.randint(35, 95))
                search_box.press('Enter')
                page.wait_for_timeout(random.randint(600, 1100))
        deadline = start + 90
        while time.monotonic() < deadline:
            anchors = page.query_selector_all('a[href*="/watch?v="]')
            for anchor in anchors:
                href = anchor.get_attribute('href') or ''
                if trimmed and trimmed in href:
                    anchor.hover()
                    page.wait_for_timeout(random.randint(120, 260))
                    anchor.click()
                    found = True
                    return visited, found, int(time.monotonic() - start)
            page.mouse.wheel(0, random.randint(320, 760))
            page.wait_for_timeout(random.randint(320, 680))
            visited += 1
        return visited, found, int(time.monotonic() - start)


__all__ = ["YouTubeHandler"]
