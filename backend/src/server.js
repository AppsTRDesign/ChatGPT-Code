const http = require('http');
const app = require('./app');
const env = require('../config/env');
const { initializeSockets } = require('../sockets');

const server = http.createServer(app);
initializeSockets(server, env.corsOrigin);

server.listen(env.port, function () {
  console.log('[backend] server running on port', env.port);
});
