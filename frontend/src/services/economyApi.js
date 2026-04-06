const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:3001/api/v1';

function headers(token) {
  const h = { 'Content-Type': 'application/json' };
  if (token) h.Authorization = `Bearer ${token}`;
  return h;
}

async function request(path, method = 'GET', token, body) {
  const res = await fetch(`${API_BASE_URL}${path}`, {
    method,
    headers: headers(token),
    body: body ? JSON.stringify(body) : undefined
  });
  const payload = await res.json();
  if (!res.ok) throw new Error(payload.error || `Economy API error: ${res.status}`);
  return payload.data;
}

export function fetchEconomyOverview(token) {
  return request('/economy/overview', 'GET', token);
}

export function fetchJobs(cityId, countryId) {
  return request(`/economy/jobs?cityId=${cityId}&countryId=${countryId}`);
}

export function performWork(token, jobId) {
  return request('/economy/work', 'POST', token, { jobId });
}

export function transferSupport(token, cityId, amount) {
  return request('/economy/transfer-support', 'POST', token, { cityId, amount });
}
