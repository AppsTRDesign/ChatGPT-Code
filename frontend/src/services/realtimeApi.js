const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:3001/api/v1';

async function parseResponse(response) {
  if (!response.ok) {
    const body = await response.json().catch(() => ({}));
    throw new Error(body.error || `API error: ${response.status}`);
  }
  const data = await response.json();
  return data.data;
}

export async function fetchRealtimeSnapshot(token) {
  const response = await fetch(`${API_BASE_URL}/realtime/snapshot`, {
    headers: { Authorization: `Bearer ${token}` }
  });
  return parseResponse(response);
}

export async function publishDemoRealtime(token) {
  const response = await fetch(`${API_BASE_URL}/realtime/publish-demo`, {
    method: 'POST',
    headers: { Authorization: `Bearer ${token}` }
  });
  return parseResponse(response);
}
