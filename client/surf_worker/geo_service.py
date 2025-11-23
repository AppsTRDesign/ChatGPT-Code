from pathlib import Path
from typing import Dict, Optional

import requests

try:  # noqa: WPS433
    import geoip2.database
except Exception:  # noqa: BLE001
    geoip2 = None


class GeoService:
    def __init__(self, assets_dir, log):
        self.log = log
        self._geo_cache: Optional[dict] = None
        self.assets_dir = Path(assets_dir)

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
        if geoip2 and info['ip']:
            try:
                info.update(self._resolve_local_mmdb(info['ip']))
            except Exception as exc:  # noqa: BLE001
                self.log(f'GeoIP okunamadı: {exc}')
        self._geo_cache = info
        return info

    def _resolve_local_mmdb(self, ip: str) -> Dict[str, Optional[object]]:
        result: Dict[str, Optional[object]] = {
            'ip': ip,
            'country': None,
            'country_code': None,
            'continent': None,
            'city': None,
            'lat': None,
            'lon': None,
            'asn': None,
            'isp': None,
            'network': None,
        }

        def safe_reader(path: Path):
            return geoip2.database.Reader(str(path)) if path.exists() else None

        city_db = self.assets_dir / 'GeoLite2-City.mmdb'
        country_db = self.assets_dir / 'GeoLite2-Country.mmdb'
        asn_db = self.assets_dir / 'GeoLite2-ASN.mmdb'

        try:
            city_reader = safe_reader(city_db)
            if city_reader:
                city_resp = city_reader.city(ip)
                result['country'] = city_resp.country.name
                result['country_code'] = city_resp.country.iso_code
                result['continent'] = city_resp.continent.name
                result['city'] = city_resp.city.name
                result['lat'] = city_resp.location.latitude
                result['lon'] = city_resp.location.longitude
                city_reader.close()
        except Exception:
            pass

        try:
            country_reader = safe_reader(country_db)
            if country_reader:
                country_resp = country_reader.country(ip)
                result['country'] = result['country'] or country_resp.country.name
                result['country_code'] = result['country_code'] or country_resp.country.iso_code
                result['continent'] = result['continent'] or country_resp.continent.name
                country_reader.close()
        except Exception:
            pass

        try:
            asn_reader = safe_reader(asn_db)
            if asn_reader:
                asn_resp = asn_reader.asn(ip)
                result['asn'] = asn_resp.autonomous_system_number
                result['isp'] = asn_resp.autonomous_system_organization
                result['network'] = str(asn_resp.network)
                asn_reader.close()
        except Exception:
            pass

        return {k: v for k, v in result.items() if v is not None}


__all__ = ["GeoService"]
