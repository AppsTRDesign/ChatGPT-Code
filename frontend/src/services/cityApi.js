import { apiGet } from './apiClient';

export function fetchCityDetail(countryCode, cityName) {
  return apiGet(`/cities/${countryCode}/${encodeURIComponent(cityName)}`);
}

export function fetchCityRankings(limit = 20) {
  return apiGet(`/cities/rankings?limit=${limit}`);
}

export function fetchCityHistory(cityId, days = 30) {
  return apiGet(`/stats/cities/${cityId}/history?days=${days}`);
}
