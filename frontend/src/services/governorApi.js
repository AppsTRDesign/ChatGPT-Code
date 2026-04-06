const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:3001/api/v1';

function authHeaders(token) {
  const headers = { 'Content-Type': 'application/json' };
  if (token) headers.Authorization = `Bearer ${token}`;
  return headers;
}

async function request(path, method = 'GET', token, body) {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    method,
    headers: authHeaders(token),
    body: body ? JSON.stringify(body) : undefined
  });

  const payload = await response.json();

  if (!response.ok) {
    throw new Error(payload.error || `Governor API error: ${response.status}`);
  }

  return payload.data;
}

export function fetchCityGovernor(cityId) {
  return request(`/governors/cities/${cityId}`);
}

export function fetchCityProjects(cityId) {
  return request(`/governors/cities/${cityId}/projects`);
}

export function startCityProject(token, cityId, data) {
  return request(`/governors/cities/${cityId}/projects`, 'POST', token, data);
}

export function contributeProjectEffort(token, cityId, projectId, effortPoints) {
  return request(`/governors/cities/${cityId}/projects/${projectId}/effort`, 'POST', token, { effortPoints });
}
