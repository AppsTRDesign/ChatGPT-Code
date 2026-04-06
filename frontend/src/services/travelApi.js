const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:3001/api/v1';

function authHeaders(token) {
  return {
    'Content-Type': 'application/json',
    Authorization: `Bearer ${token}`
  };
}

async function call(path, method, token, body) {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    method,
    headers: authHeaders(token),
    body: body ? JSON.stringify(body) : undefined
  });

  if (!response.ok) {
    const payload = await response.json().catch(() => ({}));
    throw new Error(payload.error || `Travel API error: ${response.status}`);
  }

  const payload = await response.json();
  return payload.data;
}

export function fetchTravelQuote(token, departureCityId, arrivalCityId) {
  return call('/travel/quote', 'POST', token, { departureCityId, arrivalCityId });
}

export function startTravel(token, departureCityId, arrivalCityId) {
  return call('/travel/start', 'POST', token, { departureCityId, arrivalCityId });
}

export function fetchActiveTravel(token) {
  return call('/travel/active', 'GET', token);
}
