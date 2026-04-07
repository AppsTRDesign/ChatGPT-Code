const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:3001/api/v1';

async function parseResponse(response) {
  if (!response.ok) {
    const body = await response.json().catch(() => ({}));
    throw new Error(body.error || `API error: ${response.status}`);
  }
  const data = await response.json();
  return data.data;
}

export async function fetchWarList() {
  const response = await fetch(`${API_BASE_URL}/war/wars`);
  return parseResponse(response);
}

export async function fetchWarOverview(token) {
  const response = await fetch(`${API_BASE_URL}/war/overview`, {
    headers: { Authorization: `Bearer ${token}` }
  });
  return parseResponse(response);
}

export async function declareWar(token, defenderCountryId, targetRegionId) {
  const response = await fetch(`${API_BASE_URL}/war/declare`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify({ defenderCountryId, targetRegionId: targetRegionId || null })
  });
  return parseResponse(response);
}

export async function reinforceBattle(token, battleId, side, energySpend) {
  const response = await fetch(`${API_BASE_URL}/war/battles/${battleId}/reinforce`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify({ side, energySpend })
  });
  return parseResponse(response);
}

export async function resolveBattle(token, battleId) {
  const response = await fetch(`${API_BASE_URL}/war/battles/${battleId}/resolve`, {
    method: 'POST',
    headers: {
      Authorization: `Bearer ${token}`
    }
  });
  return parseResponse(response);
}
