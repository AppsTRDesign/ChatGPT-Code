const path = require('path');
require('dotenv').config({
  path: path.resolve(__dirname, '../.env')
});

const { Server } = require('socket.io');
const mysql = require('mysql2/promise');

function requireEnv(name) {
  const value = process.env[name];
  if (!value) throw new Error(`Missing required env var: ${name}`);
  return value;
}

console.log('DB HOST:', process.env.DB_HOST);

const socketPort = Number(requireEnv('SC_PORT'));
const io = new Server(socketPort, { cors: { origin: '*' } });

const pool = mysql.createPool({
  host: requireEnv('DB_HOST'),
  port: Number(requireEnv('DB_PORT')),
  user: requireEnv('DB_USER'),
  password: requireEnv('DB_PASS'),
  database: requireEnv('DB_NAME')
});

async function completeTravel(travel) {
  const conn = await pool.getConnection();
  try {
    await conn.beginTransaction();

    const [[dest]] = await conn.query('SELECT country_id FROM regions WHERE id = ?', [travel.to_region_id]);
    if (!dest) throw new Error('Destination region missing');

    await conn.query('UPDATE player_profiles SET current_region_id=?, current_country_id=? WHERE user_id=?', [travel.to_region_id, dest.country_id, travel.user_id]);
    await conn.query('UPDATE player_travel SET status="completed" WHERE user_id=? AND status="traveling"', [travel.user_id]);
    await conn.query('INSERT INTO travel_logs(user_id,from_region_id,to_region_id,status,started_at,completed_at) VALUES(?,?,?,"completed",NOW(),NOW())', [travel.user_id, travel.from_region_id, travel.to_region_id]);

    await conn.commit();
  } catch (err) {
    await conn.rollback();
    throw err;
  } finally {
    conn.release();
  }
}

async function emitProgress() {
  const [rows] = await pool.query(`
    SELECT pt.user_id, pt.from_region_id, pt.to_region_id, pt.start_time, pt.end_time,
           fr.lat AS from_lat, fr.lng AS from_lng, tr.lat AS to_lat, tr.lng AS to_lng
    FROM player_travel pt
    JOIN regions fr ON fr.id = pt.from_region_id
    JOIN regions tr ON tr.id = pt.to_region_id
    WHERE pt.status="traveling"
  `);

  const now = Date.now();
  for (const t of rows) {
    const start = new Date(t.start_time).getTime();
    const end = new Date(t.end_time).getTime();
    const remaining = Math.max(0, Math.ceil((end - now) / 1000));
    const progress = Math.min(1, Math.max(0, (now - start) / (end - start || 1)));
    const lat = Number(t.from_lat) + (Number(t.to_lat) - Number(t.from_lat)) * progress;
    const lng = Number(t.from_lng) + (Number(t.to_lng) - Number(t.from_lng)) * progress;

    io.emit('travel_progress', {
      user_id: t.user_id,
      from_region_id: t.from_region_id,
      to_region_id: t.to_region_id,
      remaining_seconds: remaining,
      progress,
      lat,
      lng
    });

    if (remaining <= 0) {
      await completeTravel(t);
      io.emit('travel_complete', { user_id: t.user_id, to_region_id: t.to_region_id });
    }
  }
}

setInterval(() => emitProgress().catch(() => {}), 1000);
io.on('connection', () => {});
console.log(`Socket server running on :${socketPort}`);
