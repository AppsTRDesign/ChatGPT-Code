const path = require('path');
require('dotenv').config({
  path: path.resolve(__dirname, '../.env')
});

const { Server } = require('socket.io');
const mysql = require('mysql2/promise');

function requireEnv(name) {
  const value = process.env[name];
  if (!value) {
    throw new Error(`Missing required env var: ${name}`);
  }
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

async function emitProgress() {
  const [rows] = await pool.query('SELECT user_id, from_region_id, to_region_id, start_time, end_time FROM player_travel WHERE status="traveling"');
  const now = Date.now();
  for (const t of rows) {
    const end = new Date(t.end_time).getTime();
    const remaining = Math.max(0, Math.ceil((end - now) / 1000));
    io.emit('travel_progress', { user_id: t.user_id, from_region_id: t.from_region_id, to_region_id: t.to_region_id, remaining_seconds: remaining });
    if (remaining <= 0) {
      io.emit('travel_complete', { user_id: t.user_id, to_region_id: t.to_region_id });
    }
  }
}

setInterval(() => emitProgress().catch(() => {}), 1000);
io.on('connection', () => {});
console.log(`Socket server running on :${socketPort}`);
