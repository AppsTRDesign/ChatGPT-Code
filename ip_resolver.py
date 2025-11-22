from __future__ import annotations

from dataclasses import dataclass
from pathlib import Path
from typing import Dict, Optional

try:  # noqa: WPS433
    import geoip2.database
except Exception:  # noqa: BLE001
    geoip2 = None


@dataclass
class IPResolver:
    """Thin helper around GeoIP2 city/country/asn readers.

    The MaxMind mmdb files are not committed; place them under the provided paths
    (e.g., ``client/assets/GeoLite2-City.mmdb``) before running.
    """

    db_path_country: str
    db_path_asn: str
    db_path_city: str

    def resolve(self, ip: str) -> Dict[str, Optional[object]]:
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
        if not ip:
            return result

        if not geoip2:
            return result

        def safe_reader(path: str):
            p = Path(path)
            return geoip2.database.Reader(str(p)) if p.exists() else None

        try:
            city_reader = safe_reader(self.db_path_city)
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
            country_reader = safe_reader(self.db_path_country)
            if country_reader:
                country_resp = country_reader.country(ip)
                result['country'] = result['country'] or country_resp.country.name
                result['country_code'] = result['country_code'] or country_resp.country.iso_code
                result['continent'] = result['continent'] or country_resp.continent.name
                country_reader.close()
        except Exception:
            pass

        try:
            asn_reader = safe_reader(self.db_path_asn)
            if asn_reader:
                asn_resp = asn_reader.asn(ip)
                result['asn'] = asn_resp.autonomous_system_number
                result['isp'] = asn_resp.autonomous_system_organization
                result['network'] = str(asn_resp.network)
                asn_reader.close()
        except Exception:
            pass

        return result
