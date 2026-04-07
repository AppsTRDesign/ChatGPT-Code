import { apiGet, apiPatch, apiPut } from './apiClient';

export function fetchAdminDashboard() {
  return apiGet('/admin/dashboard');
}

export function fetchModerationUsers(limit) {
  return apiGet(`/admin/moderation/users?limit=${Number(limit) || 20}`);
}

export function fetchModerationActions(limit) {
  return apiGet(`/admin/moderation/actions?limit=${Number(limit) || 20}`);
}

export function updateModerationUser(userId, status, reason) {
  return apiPatch(`/admin/moderation/users/${userId}`, { status, reason });
}

export function fetchBalancingSettings() {
  return apiGet('/admin/balancing/settings');
}

export function updateBalancingCategory(category, settings) {
  return apiPut(`/admin/balancing/settings/${category}`, { settings });
}

export function fetchTranslations(languageCode, domain) {
  const params = new URLSearchParams();
  if (languageCode) params.set('language', languageCode);
  if (domain) params.set('domain', domain);
  return apiGet(`/admin/i18n/translations?${params.toString()}`);
}

export function updateTranslation(key, languageCode, value, isApproved) {
  return apiPut(`/admin/i18n/translations/${encodeURIComponent(key)}`, {
    languageCode,
    value,
    isApproved
  });
}
