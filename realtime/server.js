const { Server } = require('socket.io');
const mysql = require('mysql2/promise');

const io = new Server(3001, { cors: { origin: '*' } });

const pool = mysql.createPool({
  host: process.env.DB_HOST || '127.0.0.1',
  port: Number(process.env.DB_PORT || 3306),
  user: process.env.DB_USER || 'mmo_user',
  password: process.env.DB_PASS || 'change_me',
  database: process.env.DB_NAME || 'mmo_game'
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

setInterval(() => emitProgress().catch(()=>{}), 1000);
io.on('connection', () => {});
console.log('Socket server running on :3001');
