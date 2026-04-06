const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:3001/api/v1';

function getStoredToken() {
  if (typeof localStorage === 'undefined') return '';
  return localStorage.getItem('accessToken') || '';
}

function buildHeaders(extraHeaders) {
  const token = getStoredToken();
  const headers = Object.assign({}, extraHeaders || {});
  if (token) headers.Authorization = `Bearer ${token}`;
  return headers;
}

async function parseResponse(response) {
  const payload = await response.json().catch(() => ({}));
  if (!response.ok) {
    throw new Error(payload.error || `API error: ${response.status}`);
  }
  return payload.data;
}

export async function apiGet(path, extraHeaders) {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    headers: buildHeaders(extraHeaders)
  });
  return parseResponse(response);
}

export async function apiPost(path, body, extraHeaders) {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    method: 'POST',
    headers: buildHeaders(Object.assign({ 'Content-Type': 'application/json' }, extraHeaders || {})),
    body: JSON.stringify(body || {})
  });
  return parseResponse(response);
}

export async function apiPatch(path, body, extraHeaders) {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    method: 'PATCH',
    headers: buildHeaders(Object.assign({ 'Content-Type': 'application/json' }, extraHeaders || {})),
    body: JSON.stringify(body || {})
  });
  return parseResponse(response);
}
