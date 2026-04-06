const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:3001/api/v1';

async function parseResponse(response) {
  if (!response.ok) {
    const body = await response.json().catch(() => ({}));
    throw new Error(body.error || `API error: ${response.status}`);
  }
  const data = await response.json();
  return data.data;
}

export async function fetchNotifications(token, limit) {
  const response = await fetch(`${API_BASE_URL}/notifications?limit=${Number(limit) || 30}`, {
    headers: { Authorization: `Bearer ${token}` }
  });
  return parseResponse(response);
}

export async function markNotificationRead(token, notificationId) {
  const response = await fetch(`${API_BASE_URL}/notifications/${notificationId}/read`, {
    method: 'PATCH',
    headers: { Authorization: `Bearer ${token}` }
  });
  return parseResponse(response);
}

export async function dispatchDemoNotification(token) {
  const response = await fetch(`${API_BASE_URL}/notifications/demo`, {
    method: 'POST',
    headers: { Authorization: `Bearer ${token}` }
  });
  return parseResponse(response);
}
