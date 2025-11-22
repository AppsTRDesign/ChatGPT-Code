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
            'country_code': geo.get('country_code'),
            'country': geo.get('country'),
            'continent': geo.get('continent'),
            'city': geo.get('city'),
            'lat': geo.get('lat'),
            'lon': geo.get('lon'),
            'asn': geo.get('asn'),
            'isp': geo.get('isp'),
            'network': geo.get('network'),
            'device': 'Mobile' if site.get('mobile') else 'Desktop',
            'platform': platform.system(),
            'user_agent': ua[:220],
            'clicks': metrics.get('clicks', 0),
            'scrolls': metrics.get('scrolls', 0),
            'highlights': metrics.get('highlights', 0),
            'forms': metrics.get('forms', 0),
            'media': metrics.get('media', 0),
        }
        return {k: v for k, v in payload.items() if v not in (None, '')}


__all__ = ["TelemetryBuilder"]
