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
  if (!res.ok) {
    throw new Error(payload.error || `Politics API error: ${res.status}`);
  }

  return payload.data;
}

export function fetchElections(status = 'active') {
  return request(`/politics/elections?status=${status}`);
}

export function createElection(token, payload) {
  return request('/politics/elections', 'POST', token, payload);
}

export function joinElectionCandidate(token, electionId, payload) {
  return request(`/politics/elections/${electionId}/candidate`, 'POST', token, payload);
}

export function voteElection(token, electionId, candidateUserId) {
  return request(`/politics/elections/${electionId}/vote`, 'POST', token, { candidateUserId });
}

export function fetchElectionResults(electionId) {
  return request(`/politics/elections/${electionId}/results`);
}

export function finalizeElection(token, electionId) {
  return request(`/politics/elections/${electionId}/finalize`, 'POST', token);
}

export function proposeLaw(token, payload) {
  return request('/politics/laws', 'POST', token, payload);
}

export function voteLaw(token, lawId, voteValue) {
  return request(`/politics/laws/${lawId}/vote`, 'POST', token, { voteValue });
}

export function finalizeLaw(token, lawId) {
  return request(`/politics/laws/${lawId}/finalize`, 'POST', token);
}
