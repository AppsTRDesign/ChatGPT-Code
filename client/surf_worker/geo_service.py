from typing import Dict, Optional

from ip_resolver import IPResolver
import requests


class GeoService:
    def __init__(self, assets_dir, log):
        self.log = log
        self._geo_cache: Optional[dict] = None
        self.ip_resolver = IPResolver(
            db_path_country=str(assets_dir / 'GeoLite2-Country.mmdb'),
            db_path_asn=str(assets_dir / 'GeoLite2-ASN.mmdb'),
            db_path_city=str(assets_dir / 'GeoLite2-City.mmdb'),
        )

    def detect(self) -> Dict:
        if self._geo_cache is not None:
            return self._geo_cache
        info = {
            'ip': '',
            'country': '',
            'country_code': '',
            'continent': '',
            'city': '',
            'lat': None,
            'lon': None,
            'asn': None,
            'isp': '',
            'network': '',
        }
        try:
            ip_resp = requests.get('https://api.ipify.org', timeout=4)
            ip_resp.raise_for_status()
            info['ip'] = ip_resp.text.strip()
        except Exception as exc:  # noqa: BLE001
            self.log(f'IP tespit hatası: {exc}')
        if self.ip_resolver and info['ip']:
            try:
                resolved = self.ip_resolver.resolve(info['ip'])
                for key, val in resolved.items():
                    if val is not None:
                        info[key] = val
            except Exception as exc:  # noqa: BLE001
                self.log(f'GeoIP okunamadı: {exc}')
        self._geo_cache = info
        return info


__all__ = ["GeoService"]
