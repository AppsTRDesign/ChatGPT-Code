import { apiGet, apiPost } from './apiClient';

export async function login(identity, password) {
  const data = await apiPost('/auth/login', { identity, password });
  if (typeof localStorage !== 'undefined' && data && data.tokens && data.tokens.accessToken) {
    localStorage.setItem('accessToken', data.tokens.accessToken);
    localStorage.setItem('refreshToken', data.tokens.refreshToken || '');
  }
  return data;
}

export async function register(payload) {
  return apiPost('/auth/register', payload);
}

export async function me() {
  return apiGet('/auth/me');
}

export async function logout() {
  const refreshToken = typeof localStorage !== 'undefined' ? localStorage.getItem('refreshToken') : '';
  await apiPost('/auth/logout', { refreshToken });
  if (typeof localStorage !== 'undefined') {
    localStorage.removeItem('accessToken');
    localStorage.removeItem('refreshToken');
  }
}
