const { Server } = require('socket.io');
const { verifyAccessToken } = require('../core/jwt');
const { setIo } = require('./socket-state');

function initializeSockets(httpServer, corsOrigin) {
  const io = new Server(httpServer, {
    cors: {
      origin: corsOrigin,
      credentials: true
    }
  });

  io.use((socket, next) => {
    try {
      const authHeader = socket.handshake.auth && socket.handshake.auth.token;
      const queryToken = socket.handshake.query && socket.handshake.query.token;
      const raw = authHeader || queryToken || '';
      const token = String(raw).replace(/^Bearer\s+/i, '');

      if (!token) {
        socket.user = null;
        next();
        return;
      }

      socket.user = verifyAccessToken(token);
      next();
    } catch (error) {
      next(new Error('Unauthorized socket token'));
    }
  });

  io.on('connection', (socket) => {
    if (socket.user && socket.user.sub) {
      socket.join(`user:${socket.user.sub}`);
    }

    socket.on('subscribe:country', (countryId) => {
      if (countryId) socket.join(`country:${Number(countryId)}`);
    });

    socket.on('subscribe:city', (cityId) => {
      if (cityId) socket.join(`city:${Number(cityId)}`);
    });

    socket.on('subscribe:chat', (scopeType, scopeId) => {
      if (scopeType && scopeId) socket.join(`chat:${scopeType}:${Number(scopeId)}`);
    });
  });

  setIo(io);
  return io;
}

module.exports = {
  initializeSockets
};
