const pool = require('../../core/db');
const HttpError = require('../../core/http-error');

function isActiveStatus(status) {
  return status === 'declared' || status === 'active';
}

async function getUserContext(userId) {
  const [rows] = await pool.query(
    `SELECT u.id, u.level, u.energy, u.current_country_id,
            COALESCE(up.reputation, 0) AS reputation
     FROM users u
     LEFT JOIN user_profiles up ON up.user_id = u.id
     WHERE u.id = ?
     LIMIT 1`,
    [Number(userId)]
  );

  if (!rows.length) {
    throw new HttpError(404, 'User not found');
  }

  if (!rows[0].current_country_id) {
    throw new HttpError(400, 'User has no active country');
  }

  return rows[0];
}

async function ensureActivePresident(userId, countryId) {
  const [rows] = await pool.query(
    `SELECT id
     FROM presidents
     WHERE user_id = ? AND country_id = ? AND is_active = 1
     LIMIT 1`,
    [Number(userId), Number(countryId)]
  );

  if (!rows.length) {
    throw new HttpError(403, 'Only active president can perform this action');
  }
}

async function findOrValidateTargetRegion(defenderCountryId, targetRegionId) {
  if (targetRegionId) {
    const [rows] = await pool.query(
      `SELECT id, country_id
       FROM regions
       WHERE id = ?
       LIMIT 1`,
      [Number(targetRegionId)]
    );

    if (!rows.length) throw new HttpError(404, 'Target region not found');
    if (Number(rows[0].country_id) !== Number(defenderCountryId)) {
      throw new HttpError(400, 'Target region does not belong to defender country');
    }

    return rows[0];
  }

  const [rows] = await pool.query(
    `SELECT id, country_id
     FROM regions
     WHERE country_id = ?
     ORDER BY strategic_value DESC, id ASC
     LIMIT 1`,
    [Number(defenderCountryId)]
  );

  if (!rows.length) {
    throw new HttpError(400, 'Defender country has no regions');
  }

  return rows[0];
}

async function listWars() {
  const [rows] = await pool.query(
    `SELECT w.id, w.status, w.war_score_attacker, w.war_score_defender,
            w.war_exhaustion_attacker, w.war_exhaustion_defender,
            w.declared_at, w.ended_at,
            a.id AS attacker_country_id, a.name AS attacker_country_name, a.code AS attacker_country_code,
            d.id AS defender_country_id, d.name AS defender_country_name, d.code AS defender_country_code
     FROM wars w
     JOIN countries a ON a.id = w.attacker_country_id
     JOIN countries d ON d.id = w.defender_country_id
     ORDER BY w.id DESC
     LIMIT 50`
  );

  return rows.map((row) => ({
    id: Number(row.id),
    status: row.status,
    declaredAt: row.declared_at,
    endedAt: row.ended_at,
    warScore: {
      attacker: Number(row.war_score_attacker),
      defender: Number(row.war_score_defender)
    },
    warExhaustion: {
      attacker: Number(row.war_exhaustion_attacker),
      defender: Number(row.war_exhaustion_defender)
    },
    attackerCountry: {
      id: Number(row.attacker_country_id),
      name: row.attacker_country_name,
      code: row.attacker_country_code
    },
    defenderCountry: {
      id: Number(row.defender_country_id),
      name: row.defender_country_name,
      code: row.defender_country_code
    }
  }));
}

async function declareWar(userId, defenderCountryId, targetRegionId) {
  const user = await getUserContext(userId);
  const attackerCountryId = Number(user.current_country_id);
  const defenderId = Number(defenderCountryId);

  if (!defenderId) throw new HttpError(400, 'defenderCountryId is required');
  if (attackerCountryId === defenderId) throw new HttpError(400, 'A country cannot declare war on itself');

  await ensureActivePresident(userId, attackerCountryId);

  const [countries] = await pool.query('SELECT id FROM countries WHERE id IN (?, ?)', [attackerCountryId, defenderId]);
  if (countries.length !== 2) throw new HttpError(404, 'Attacker or defender country not found');

  const [existing] = await pool.query(
    `SELECT id, status
     FROM wars
     WHERE ((attacker_country_id = ? AND defender_country_id = ?) OR (attacker_country_id = ? AND defender_country_id = ?))
       AND status IN ('declared', 'active')
     LIMIT 1`,
    [attackerCountryId, defenderId, defenderId, attackerCountryId]
  );

  if (existing.length && isActiveStatus(existing[0].status)) {
    throw new HttpError(400, 'An active war already exists between these countries');
  }

  const targetRegion = await findOrValidateTargetRegion(defenderId, targetRegionId);

  const connection = await pool.getConnection();
  try {
    await connection.beginTransaction();

    const [warInsert] = await connection.query(
      `INSERT INTO wars (attacker_country_id, defender_country_id, status, declared_at)
       VALUES (?, ?, 'active', NOW())`,
      [attackerCountryId, defenderId]
    );

    const warId = Number(warInsert.insertId);

    const [battleInsert] = await connection.query(
      `INSERT INTO battles (war_id, target_region_id, status, starts_at)
       VALUES (?, ?, 'active', NOW())`,
      [warId, Number(targetRegion.id)]
    );

    const battleId = Number(battleInsert.insertId);

    await connection.query(
      `UPDATE regions
       SET battle_status = 'active'
       WHERE id = ?`,
      [Number(targetRegion.id)]
    );

    await connection.query(
      `INSERT INTO battle_logs (battle_id, user_id, action_type, payload_json)
       VALUES (?, ?, 'war_declared', JSON_OBJECT('warId', ?, 'attackerCountryId', ?, 'defenderCountryId', ?))`,
      [battleId, Number(userId), warId, attackerCountryId, defenderId]
    );

    await connection.commit();

    return {
      warId,
      battleId,
      attackerCountryId,
      defenderCountryId: defenderId,
      targetRegionId: Number(targetRegion.id)
    };
  } catch (error) {
    await connection.rollback();
    throw error;
  } finally {
    connection.release();
  }
}

async function reinforceBattle(userId, battleId, side, energySpend) {
  const user = await getUserContext(userId);
  const normalizedSide = side === 'defender' ? 'defender' : 'attacker';
  const spend = Number(energySpend || 0);

  if (!Number.isFinite(spend) || spend <= 0) throw new HttpError(400, 'energySpend must be positive');
  if (Number(user.energy) < spend) throw new HttpError(400, 'Insufficient energy');

  const [battleRows] = await pool.query(
    `SELECT b.id, b.status, b.war_id,
            w.attacker_country_id, w.defender_country_id
     FROM battles b
     JOIN wars w ON w.id = b.war_id
     WHERE b.id = ?
     LIMIT 1`,
    [Number(battleId)]
  );

  if (!battleRows.length) throw new HttpError(404, 'Battle not found');
  const battle = battleRows[0];
  if (battle.status !== 'active') throw new HttpError(400, 'Battle is not active');

  const requiredCountryId = normalizedSide === 'attacker' ? Number(battle.attacker_country_id) : Number(battle.defender_country_id);
  if (Number(user.current_country_id) !== requiredCountryId) {
    throw new HttpError(403, 'User is not eligible for selected battle side');
  }

  const contributionPower = Math.max(1, Math.round(spend * (1 + Number(user.level || 1) * 0.05) * (1 + Number(user.reputation || 0) / 1000)));

  const connection = await pool.getConnection();
  try {
    await connection.beginTransaction();

    await connection.query('UPDATE users SET energy = GREATEST(energy - ?, 0) WHERE id = ?', [spend, Number(userId)]);

    await connection.query(
      `INSERT INTO battle_participations (battle_id, war_id, user_id, side, contribution_power, contribution_energy)
       VALUES (?, ?, ?, ?, ?, ?)`,
      [Number(battle.id), Number(battle.war_id), Number(userId), normalizedSide, contributionPower, spend]
    );

    if (normalizedSide === 'attacker') {
      await connection.query('UPDATE battles SET attacker_power = attacker_power + ? WHERE id = ?', [contributionPower, Number(battle.id)]);
    } else {
      await connection.query('UPDATE battles SET defender_power = defender_power + ? WHERE id = ?', [contributionPower, Number(battle.id)]);
    }

    await connection.query(
      `INSERT INTO battle_logs (battle_id, user_id, action_type, payload_json)
       VALUES (?, ?, 'battle_reinforcement', JSON_OBJECT('side', ?, 'power', ?, 'energy', ?))`,
      [Number(battle.id), Number(userId), normalizedSide, contributionPower, spend]
    );

    await connection.commit();

    return {
      battleId: Number(battle.id),
      warId: Number(battle.war_id),
      side: normalizedSide,
      spentEnergy: spend,
      contributionPower
    };
  } catch (error) {
    await connection.rollback();
    throw error;
  } finally {
    connection.release();
  }
}

async function resolveBattle(userId, battleId) {
  await getUserContext(userId);

  const [battleRows] = await pool.query(
    `SELECT b.id, b.status, b.attacker_power, b.defender_power, b.war_id, b.target_region_id,
            w.attacker_country_id, w.defender_country_id, w.status AS war_status
     FROM battles b
     JOIN wars w ON w.id = b.war_id
     WHERE b.id = ?
     LIMIT 1`,
    [Number(battleId)]
  );

  if (!battleRows.length) throw new HttpError(404, 'Battle not found');
  const battle = battleRows[0];

  if (battle.status !== 'active') throw new HttpError(400, 'Battle is not active');
  if (!isActiveStatus(battle.war_status)) throw new HttpError(400, 'War is not active');

  const [presidentRows] = await pool.query(
    `SELECT id
     FROM presidents
     WHERE user_id = ? AND is_active = 1 AND country_id IN (?, ?)
     LIMIT 1`,
    [Number(userId), Number(battle.attacker_country_id), Number(battle.defender_country_id)]
  );

  if (!presidentRows.length) {
    throw new HttpError(403, 'Only attacker/defender active presidents can resolve battles');
  }

  const attackerPower = Number(battle.attacker_power || 0);
  const defenderPower = Number(battle.defender_power || 0);
  const winnerSide = attackerPower > defenderPower ? 'attacker' : 'defender';
  const winnerCountryId = winnerSide === 'attacker' ? Number(battle.attacker_country_id) : Number(battle.defender_country_id);
  const loserCountryId = winnerSide === 'attacker' ? Number(battle.defender_country_id) : Number(battle.attacker_country_id);
  const scoreDelta = Math.max(5, Math.round(Math.abs(attackerPower - defenderPower) / 10));

  const connection = await pool.getConnection();
  try {
    await connection.beginTransaction();

    await connection.query(
      `UPDATE battles
       SET status = 'completed', ends_at = NOW(), winner_country_id = ?,
           attacker_score = ?, defender_score = ?
       WHERE id = ?`,
      [winnerCountryId, attackerPower, defenderPower, Number(battle.id)]
    );

    await connection.query(
      `UPDATE regions
       SET controlling_country_id = ?, battle_status = 'peace'
       WHERE id = ?`,
      [winnerCountryId, Number(battle.target_region_id)]
    );

    if (winnerSide === 'attacker') {
      await connection.query(
        `UPDATE wars
         SET war_score_attacker = war_score_attacker + ?,
             war_exhaustion_attacker = LEAST(100, war_exhaustion_attacker + 4),
             war_exhaustion_defender = LEAST(100, war_exhaustion_defender + 9),
             status = 'ended', ended_at = NOW(),
             peace_terms_json = JSON_OBJECT('winnerCountryId', ?, 'loserCountryId', ?, 'reason', 'battle_resolved')
         WHERE id = ?`,
        [scoreDelta, winnerCountryId, loserCountryId, Number(battle.war_id)]
      );
    } else {
      await connection.query(
        `UPDATE wars
         SET war_score_defender = war_score_defender + ?,
             war_exhaustion_attacker = LEAST(100, war_exhaustion_attacker + 9),
             war_exhaustion_defender = LEAST(100, war_exhaustion_defender + 4),
             status = 'ended', ended_at = NOW(),
             peace_terms_json = JSON_OBJECT('winnerCountryId', ?, 'loserCountryId', ?, 'reason', 'battle_resolved')
         WHERE id = ?`,
        [scoreDelta, winnerCountryId, loserCountryId, Number(battle.war_id)]
      );
    }

    await connection.query(
      `INSERT INTO battle_logs (battle_id, user_id, action_type, payload_json)
       VALUES (?, ?, 'battle_resolved', JSON_OBJECT('winnerCountryId', ?, 'winnerSide', ?, 'attackerPower', ?, 'defenderPower', ?))`,
      [Number(battle.id), Number(userId), winnerCountryId, winnerSide, attackerPower, defenderPower]
    );

    await connection.commit();

    return {
      battleId: Number(battle.id),
      warId: Number(battle.war_id),
      winnerCountryId,
      winnerSide,
      attackerPower,
      defenderPower,
      regionTransferredTo: winnerCountryId
    };
  } catch (error) {
    await connection.rollback();
    throw error;
  } finally {
    connection.release();
  }
}

async function warOverview(userId) {
  const user = await getUserContext(userId);
  const countryId = Number(user.current_country_id);

  const [warRows] = await pool.query(
    `SELECT id, attacker_country_id, defender_country_id, status, war_score_attacker, war_score_defender,
            war_exhaustion_attacker, war_exhaustion_defender, declared_at, ended_at
     FROM wars
     WHERE (attacker_country_id = ? OR defender_country_id = ?)
     ORDER BY id DESC
     LIMIT 10`,
    [countryId, countryId]
  );

  if (!warRows.length) {
    return { countryId, wars: [], battles: [] };
  }

  const warIds = warRows.map((war) => Number(war.id));
  const placeholders = warIds.map(() => '?').join(',');

  const [battleRows] = await pool.query(
    `SELECT id, war_id, target_region_id, status, starts_at, ends_at, attacker_power, defender_power, winner_country_id
     FROM battles
     WHERE war_id IN (${placeholders})
     ORDER BY id DESC`,
    warIds
  );

  return {
    countryId,
    wars: warRows,
    battles: battleRows
  };
}

module.exports = {
  listWars,
  declareWar,
  reinforceBattle,
  resolveBattle,
  warOverview
};
