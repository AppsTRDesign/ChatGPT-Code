const app = require('./app');
const env = require('../config/env');

app.listen(env.port, function () {
  console.log('[backend] server running on port', env.port);
});
