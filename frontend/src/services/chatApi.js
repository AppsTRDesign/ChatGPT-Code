const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:3001/api/v1';

async function parseResponse(response) {
  if (!response.ok) {
    const body = await response.json().catch(() => ({}));
    throw new Error(body.error || `API error: ${response.status}`);
  }
  const data = await response.json();
  return data.data;
}

export async function fetchChatMessages(scopeType, scopeId, limit) {
  const qs = new URLSearchParams({ scopeType: scopeType || 'global', scopeId: String(scopeId || 0), limit: String(limit || 30) });
  const response = await fetch(`${API_BASE_URL}/chat/messages?${qs.toString()}`);
  return parseResponse(response);
}

export async function sendChatMessage(token, scopeType, scopeId, message) {
  const response = await fetch(`${API_BASE_URL}/chat/messages`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify({ scopeType, scopeId, message })
  });
  return parseResponse(response);
}
