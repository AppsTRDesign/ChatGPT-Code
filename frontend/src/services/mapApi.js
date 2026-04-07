import { apiGet } from './apiClient';

export function fetchWorldMap() {
  return apiGet('/map/world');
}

export function fetchCountryCard(countryCode) {
  return apiGet(`/map/countries/${countryCode}`);
}

export function fetchCityCard(countryCode, cityName) {
  const query = new URLSearchParams({ countryCode, cityName }).toString();
  return apiGet(`/map/cities?${query}`);
}
