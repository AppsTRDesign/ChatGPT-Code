const pool = require('../../core/db');
const HttpError = require('../../core/http-error');

async function createElection(userId, payload) {
  const electionScope = String(payload.electionScope || 'country');
  const officeType = String(payload.officeType || 'president');
  const countryId = payload.countryId ? Number(payload.countryId) : null;
  const cityId = payload.cityId ? Number(payload.cityId) : null;
  const startsAt = payload.startsAt ? new Date(payload.startsAt) : new Date();
  const endsAt = payload.endsAt ? new Date(payload.endsAt) : new Date(Date.now() + 24 * 3600 * 1000);

  const [insert] = await pool.query(
    `INSERT INTO elections
     (election_scope, country_id, city_id, office_type, status, starts_at, ends_at, created_by_user_id)
     VALUES (?, ?, ?, ?, 'active', ?, ?, ?)`,
    [electionScope, countryId, cityId, officeType, startsAt, endsAt, userId]
  );

  return {
    id: insert.insertId,
    electionScope,
    officeType,
    countryId,
    cityId,
    status: 'active'
  };
}

async function listElections(status) {
  const where = status ? 'WHERE e.status = ?' : '';
  const params = status ? [status] : [];

  const [rows] = await pool.query(
    `SELECT e.id, e.election_scope, e.country_id, e.city_id, e.office_type, e.status, e.starts_at, e.ends_at,
            c.name AS country_name, ci.name AS city_name
     FROM elections e
     LEFT JOIN countries c ON c.id = e.country_id
     LEFT JOIN cities ci ON ci.id = e.city_id
     ${where}
     ORDER BY e.id DESC`,
    params
  );

  return rows;
}

async function joinCandidate(userId, electionId, partyName, manifesto) {
  const [eRows] = await pool.query('SELECT id, election_scope, country_id, city_id, status FROM elections WHERE id = ? LIMIT 1', [Number(electionId)]);
  const election = eRows[0];
  if (!election) throw new HttpError(404, 'Election not found');
  if (election.status !== 'active') throw new HttpError(400, 'Election is not active');

  const [uRows] = await pool.query('SELECT current_country_id, current_city_id FROM users WHERE id = ? LIMIT 1', [Number(userId)]);
  const user = uRows[0];
  if (!user) throw new HttpError(404, 'User not found');

  if (election.election_scope === 'country' && Number(user.current_country_id) !== Number(election.country_id)) {
    throw new HttpError(403, 'User is not citizen of this country');
  }

  if (election.election_scope === 'city' && Number(user.current_city_id) !== Number(election.city_id)) {
    throw new HttpError(403, 'User is not resident of this city');
  }

  await pool.query(
    `INSERT INTO election_candidates (election_id, user_id, party_name, manifesto, status)
     VALUES (?, ?, ?, ?, 'active')`,
    [Number(electionId), Number(userId), partyName || null, manifesto || null]
  );

  return { electionId: Number(electionId), userId: Number(userId), status: 'active' };
}

async function castElectionVote(userId, electionId, candidateUserId) {
  const [eRows] = await pool.query('SELECT id, election_scope, country_id, city_id, status FROM elections WHERE id = ? LIMIT 1', [Number(electionId)]);
  const election = eRows[0];
  if (!election) throw new HttpError(404, 'Election not found');
  if (election.status !== 'active') throw new HttpError(400, 'Election is not active');

  const [uRows] = await pool.query('SELECT current_country_id, current_city_id FROM users WHERE id = ? LIMIT 1', [Number(userId)]);
  const user = uRows[0];
  if (!user) throw new HttpError(404, 'User not found');

  if (election.election_scope === 'country' && Number(user.current_country_id) !== Number(election.country_id)) {
    throw new HttpError(403, 'User is not citizen of this country');
  }

  if (election.election_scope === 'city' && Number(user.current_city_id) !== Number(election.city_id)) {
    throw new HttpError(403, 'User is not resident of this city');
  }

  const [candidateRows] = await pool.query(
    `SELECT id FROM election_candidates
     WHERE election_id = ? AND user_id = ? AND status = 'active'
     LIMIT 1`,
    [Number(electionId), Number(candidateUserId)]
  );

  if (!candidateRows.length) {
    throw new HttpError(400, 'Candidate is not active in this election');
  }

  await pool.query(
    `INSERT INTO votes (election_id, voter_user_id, candidate_user_id)
     VALUES (?, ?, ?)`,
    [Number(electionId), Number(userId), Number(candidateUserId)]
  );

  return { electionId: Number(electionId), voterUserId: Number(userId), candidateUserId: Number(candidateUserId) };
}

async function electionResults(electionId) {
  const [rows] = await pool.query(
    `SELECT ec.user_id AS candidate_user_id, u.username,
            COUNT(v.id) AS vote_count
     FROM election_candidates ec
     JOIN users u ON u.id = ec.user_id
     LEFT JOIN votes v ON v.election_id = ec.election_id AND v.candidate_user_id = ec.user_id
     WHERE ec.election_id = ?
     GROUP BY ec.user_id
     ORDER BY vote_count DESC, ec.user_id ASC`,
    [Number(electionId)]
  );

  return rows;
}

async function finalizeElection(electionId) {
  const [eRows] = await pool.query('SELECT * FROM elections WHERE id = ? LIMIT 1', [Number(electionId)]);
  const election = eRows[0];
  if (!election) throw new HttpError(404, 'Election not found');

  const results = await electionResults(electionId);
  if (!results.length) {
    throw new HttpError(400, 'No candidates in election');
  }

  const winner = results[0];

  await pool.query(
    `UPDATE elections
     SET status = 'completed', result_announced_at = NOW()
     WHERE id = ?`,
    [Number(electionId)]
  );

  if (election.office_type === 'president' && election.country_id) {
    await pool.query('UPDATE presidents SET is_active = 0 WHERE country_id = ?', [Number(election.country_id)]);
    await pool.query(
      `INSERT INTO presidents (country_id, user_id, election_id, term_start, is_active)
       VALUES (?, ?, ?, NOW(), 1)`,
      [Number(election.country_id), Number(winner.candidate_user_id), Number(electionId)]
    );
    await pool.query('UPDATE countries SET active_president_user_id = ? WHERE id = ?', [Number(winner.candidate_user_id), Number(election.country_id)]);
  }

  if (election.office_type === 'governor' && election.city_id) {
    await pool.query('UPDATE governors SET is_active = 0 WHERE city_id = ?', [Number(election.city_id)]);
    await pool.query(
      `INSERT INTO governors (city_id, user_id, election_id, term_start, is_active)
       VALUES (?, ?, ?, NOW(), 1)`,
      [Number(election.city_id), Number(winner.candidate_user_id), Number(electionId)]
    );
    await pool.query('UPDATE cities SET active_governor_user_id = ? WHERE id = ?', [Number(winner.candidate_user_id), Number(election.city_id)]);
  }

  return {
    electionId: Number(electionId),
    winnerUserId: Number(winner.candidate_user_id),
    winnerVotes: Number(winner.vote_count)
  };
}

async function proposeLaw(userId, payload) {
  const countryId = Number(payload.countryId);
  const title = String(payload.title || '').trim();
  const lawType = String(payload.lawType || '').trim();
  const policyValue = payload.policyValue;

  if (!countryId || !title || !lawType) {
    throw new HttpError(400, 'countryId, title, lawType are required');
  }

  const [insert] = await pool.query(
    `INSERT INTO laws
     (country_id, proposed_by_user_id, title, law_type, payload_json, status, voting_start, voting_end)
     VALUES (?, ?, ?, ?, JSON_OBJECT('policyValue', ?), 'voting', NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY))`,
    [countryId, Number(userId), title, lawType, policyValue]
  );

  return {
    id: insert.insertId,
    countryId,
    title,
    lawType,
    status: 'voting'
  };
}

async function voteLaw(userId, lawId, voteValue) {
  const normalized = String(voteValue || '').toLowerCase();
  if (!['yes', 'no'].includes(normalized)) {
    throw new HttpError(400, 'voteValue must be yes or no');
  }

  await pool.query(
    `INSERT INTO law_votes (law_id, voter_user_id, vote_value)
     VALUES (?, ?, ?)`,
    [Number(lawId), Number(userId), normalized]
  );

  return {
    lawId: Number(lawId),
    voterUserId: Number(userId),
    voteValue: normalized
  };
}

async function finalizeLaw(lawId) {
  const [lawRows] = await pool.query('SELECT id, country_id, law_type, payload_json FROM laws WHERE id = ? LIMIT 1', [Number(lawId)]);
  const law = lawRows[0];
  if (!law) throw new HttpError(404, 'Law not found');

  const [voteRows] = await pool.query(
    `SELECT vote_value, COUNT(*) AS vote_count
     FROM law_votes
     WHERE law_id = ?
     GROUP BY vote_value`,
    [Number(lawId)]
  );

  const yesCount = Number((voteRows.find((r) => r.vote_value === 'yes') || {}).vote_count || 0);
  const noCount = Number((voteRows.find((r) => r.vote_value === 'no') || {}).vote_count || 0);

  const passed = yesCount > noCount;

  if (passed) {
    const parsedPayload = typeof law.payload_json === 'string' ? JSON.parse(law.payload_json) : law.payload_json;
    const value = parsedPayload ? parsedPayload.policyValue : null;

    await pool.query(
      `INSERT INTO country_policies
       (country_id, tax_rate, military_spending_ratio, visa_mode, work_permit_mode, immigration_difficulty, permit_duration_days, immigration_fee)
       VALUES (?, 10, 10, 'mixed', 'regulated', 50, 30, 0)
       ON DUPLICATE KEY UPDATE country_id = country_id`,
      [Number(law.country_id)]
    );

    if (law.law_type === 'tax_rate') {
      await pool.query('UPDATE country_policies SET tax_rate = ? WHERE country_id = ?', [Number(value), Number(law.country_id)]);
    }

    if (law.law_type === 'visa_mode') {
      await pool.query('UPDATE country_policies SET visa_mode = ? WHERE country_id = ?', [String(value), Number(law.country_id)]);
    }

    if (law.law_type === 'work_permit_mode') {
      await pool.query('UPDATE country_policies SET work_permit_mode = ? WHERE country_id = ?', [String(value), Number(law.country_id)]);
    }

    if (law.law_type === 'military_spending_ratio') {
      await pool.query('UPDATE country_policies SET military_spending_ratio = ? WHERE country_id = ?', [Number(value), Number(law.country_id)]);
    }
  }

  await pool.query(
    `UPDATE laws
     SET status = ?, enacted_at = IF(? = 1, NOW(), NULL)
     WHERE id = ?`,
    [passed ? 'enacted' : 'rejected', passed ? 1 : 0, Number(lawId)]
  );

  return {
    lawId: Number(lawId),
    passed,
    yesCount,
    noCount
  };
}

module.exports = {
  createElection,
  listElections,
  joinCandidate,
  castElectionVote,
  electionResults,
  finalizeElection,
  proposeLaw,
  voteLaw,
  finalizeLaw
};
