import platform
from typing import Dict


class TelemetryBuilder:
    def __init__(self, geo_service, log):
        self.geo_service = geo_service
        self.log = log

    def build(self, page, site: dict, metrics: Dict) -> Dict:
        geo = self.geo_service.detect()
        ua = ''
        try:
            if page and not page.is_closed():
                ua = page.evaluate('() => navigator.userAgent') or ''
        except Exception:
            ua = ''
        payload = {
            'ip': geo.get('ip'),
            'cc': geo.get('country_code'),
            'city': geo.get('city'),
            'lat': geo.get('lat'),
            'lon': geo.get('lon'),
            'asn': geo.get('asn'),
            'isp': geo.get('isp'),
            'net': geo.get('network'),
            'dev': 'M' if site.get('mobile') else 'D',
            'ua': ua[:220],
            'plt': platform.system(),
            'clk': metrics.get('clicks', 0),
            'scr': metrics.get('scrolls', 0),
            'sel': metrics.get('highlights', 0),
            'frm': metrics.get('forms', 0),
            'med': metrics.get('media', 0),
        }
        return {k: v for k, v in payload.items() if v not in (None, '')}


__all__ = ["TelemetryBuilder"]
