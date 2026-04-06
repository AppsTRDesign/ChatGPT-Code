'use strict';

const http = require('http');
const { Server } = require('socket.io');

const PORT = Number(process.env.SOCKET_PORT || 3001);
const server = http.createServer((req, res) => {
  if (req.url === '/health') {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify({ ok: true, service: 'realtime-socket', time: new Date().toISOString() }));
    return;
  }
  res.writeHead(404);
  res.end('Not Found');
});

const io = new Server(server, {
  cors: { origin: '*', methods: ['GET', 'POST'] },
});

io.on('connection', (socket) => {
  socket.on('room:join', (payload) => {
    const roomKey = String(payload && payload.roomKey || 'global');
    socket.join(roomKey);
    socket.emit('room:joined', { roomKey, at: Date.now() });
  });

  socket.on('war:attack', (payload) => {
    const roomKey = String(payload && payload.roomKey || 'global');
    const damage = Math.max(1, Number(payload && payload.damage || 1));
    io.to(roomKey).emit('war:damage', {
      roomKey,
      attackerCountryId: Number(payload && payload.attackerCountryId || 0),
      defenderCountryId: Number(payload && payload.defenderCountryId || 0),
      damage,
      ts: Date.now(),
    });
  });

  socket.on('upgrade:tick', (payload) => {
    const roomKey = String(payload && payload.roomKey || 'global');
    io.to(roomKey).emit('upgrade:progress', {
      userId: Number(payload && payload.userId || 0),
      stat: String(payload && payload.stat || ''),
      readyAt: String(payload && payload.readyAt || ''),
      ts: Date.now(),
    });
  });
});

server.listen(PORT, () => {
  console.log(`[realtime] socket server listening on :${PORT}`);
});
