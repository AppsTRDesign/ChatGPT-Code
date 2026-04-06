import { apiGet } from './apiClient';

export function fetchCountryDetail(countryCode) {
  return apiGet(`/countries/${countryCode}`);
}

export function fetchCountryRankings(limit = 20) {
  return apiGet(`/countries/rankings?limit=${limit}`);
}

export function fetchCountryHistory(countryCode, days = 30) {
  return apiGet(`/stats/countries/${countryCode}/history?days=${days}`);
}
