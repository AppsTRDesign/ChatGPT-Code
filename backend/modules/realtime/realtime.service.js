const pool = require('../../core/db');
const { getIo } = require('../../sockets/socket-state');
const notificationsService = require('../notifications/notifications.service');

async function userRealtimeSnapshot(userId) {
  const [rows] = await pool.query(
    `SELECT id, current_country_id, current_city_id
     FROM users
     WHERE id = ?
     LIMIT 1`,
    [Number(userId)]
  );

  if (!rows.length) {
    return { userId: Number(userId), countryId: null, cityId: null, events: [] };
  }

  const user = rows[0];

  const [travelRows] = await pool.query(
    `SELECT id, travel_status, end_time
     FROM travels
     WHERE user_id = ? AND travel_status IN ('scheduled', 'in_progress')
     ORDER BY id DESC
     LIMIT 1`,
    [Number(userId)]
  );

  const [electionRows] = await pool.query(
    `SELECT id, election_type, status, voting_starts_at, voting_ends_at
     FROM elections
     WHERE country_id = ? AND status IN ('announced', 'voting')
     ORDER BY id DESC
     LIMIT 1`,
    [Number(user.current_country_id || 0)]
  );

  const [warRows] = await pool.query(
    `SELECT id, status, declared_at
     FROM wars
     WHERE (attacker_country_id = ? OR defender_country_id = ?)
       AND status IN ('declared', 'active')
     ORDER BY id DESC
     LIMIT 1`,
    [Number(user.current_country_id || 0), Number(user.current_country_id || 0)]
  );

  return {
    userId: Number(userId),
    countryId: user.current_country_id ? Number(user.current_country_id) : null,
    cityId: user.current_city_id ? Number(user.current_city_id) : null,
    events: {
      activeTravel: travelRows[0] || null,
      activeElection: electionRows[0] || null,
      activeWar: warRows[0] || null
    }
  };
}

async function publishDemoEvents(userId) {
  const snapshot = await userRealtimeSnapshot(userId);
  const io = getIo();

  const payload = {
    generatedAt: new Date().toISOString(),
    snapshot
  };

  if (io) {
    io.to(`user:${Number(userId)}`).emit('realtime:travel', {
      type: 'travel_update',
      data: snapshot.events.activeTravel,
      generatedAt: payload.generatedAt
    });

    io.to(`user:${Number(userId)}`).emit('realtime:election', {
      type: 'election_update',
      data: snapshot.events.activeElection,
      generatedAt: payload.generatedAt
    });

    io.to(`user:${Number(userId)}`).emit('realtime:war', {
      type: 'war_update',
      data: snapshot.events.activeWar,
      generatedAt: payload.generatedAt
    });
  }

  await notificationsService.createAndDispatchUserNotification(
    Number(userId),
    'realtime',
    'Realtime events synced',
    'Travel/war/election live snapshot emitted',
    payload
  );

  return payload;
}

module.exports = {
  userRealtimeSnapshot,
  publishDemoEvents
};
