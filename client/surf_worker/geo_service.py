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

        # Fallback: harici bir servisle geo bilgisini doldurmayı dene
        if info['ip'] and (not info['country'] or not info['city'] or info['lat'] is None):
            try:
                resp = requests.get('https://ip-api.com/json/' + info['ip'], timeout=4)
                resp.raise_for_status()
                data = resp.json()
                if data.get('status') == 'success':
                    info['country'] = info['country'] or data.get('country', '')
                    info['country_code'] = info['country_code'] or data.get('countryCode', '')
                    info['continent'] = info['continent'] or data.get('continent', '')
                    info['city'] = info['city'] or data.get('city', '')
                    info['lat'] = info['lat'] if info['lat'] is not None else data.get('lat')
                    info['lon'] = info['lon'] if info['lon'] is not None else data.get('lon')
                    info['asn'] = info['asn'] if info['asn'] is not None else data.get('asname')
                    info['isp'] = info['isp'] or data.get('isp', '')
                    info['network'] = info['network'] or data.get('org', '')
            except Exception as exc:  # noqa: BLE001
                self.log(f'GeoIP yedek servisi okunamadı: {exc}')
        self._geo_cache = info
        return info


__all__ = ["GeoService"]
