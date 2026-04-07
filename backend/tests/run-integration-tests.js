const assert = require('assert');
const http = require('http');

const app = require('../src/app');
const pool = require('../core/db');

function listen(server, port) {
  return new Promise((resolve, reject) => {
    server.listen(port, '127.0.0.1', () => resolve(server.address().port));
    server.on('error', reject);
  });
}

function close(server) {
  return new Promise((resolve, reject) => {
    server.close((err) => (err ? reject(err) : resolve()));
  });
}

function requestJson(port, path) {
  return new Promise((resolve, reject) => {
    const req = http.request(
      {
        hostname: '127.0.0.1',
        port,
        path,
        method: 'GET',
        headers: { Accept: 'application/json' }
      },
      (res) => {
        let body = '';
        res.setEncoding('utf8');
        res.on('data', (chunk) => {
          body += chunk;
        });
        res.on('end', () => {
          try {
            resolve({ status: res.statusCode, json: JSON.parse(body || '{}') });
          } catch (error) {
            reject(new Error(`Invalid JSON response for ${path}: ${body}`));
          }
        });
      }
    );

    req.on('error', reject);
    req.end();
  });
}

async function ensureDbConnection() {
  const [rows] = await pool.query('SELECT 1 AS ok');
  assert.strictEqual(rows[0].ok, 1, 'DB connectivity check failed');
}

async function run() {
  const server = http.createServer(app);

  try {
    await ensureDbConnection();

    const port = await listen(server, 0);

    const health = await requestJson(port, '/api/v1/health');
    assert.strictEqual(health.status, 200, 'health endpoint status must be 200');
    assert.strictEqual(health.json.ok, true, 'health response should be ok=true');

    const rankings = await requestJson(port, '/api/v1/countries/rankings?limit=5');
    assert.strictEqual(rankings.status, 200, 'rankings endpoint status must be 200');
    assert(Array.isArray(rankings.json.data), 'rankings data must be array');
    assert(rankings.json.data.length > 0, 'rankings must contain seed countries');

    const countryCode = rankings.json.data[0].code;
    const countryDetail = await requestJson(port, `/api/v1/countries/${countryCode}`);
    assert.strictEqual(countryDetail.status, 200, 'country detail endpoint status must be 200');
    assert.strictEqual(countryDetail.json.data.code, countryCode, 'country detail code mismatch');

    console.log('✔ API health integration');
    console.log('✔ API countries rankings integration');
    console.log('✔ API country detail integration');
    console.log('All integration API tests passed.');
  } catch (error) {
    console.error(`✖ Integration tests failed: ${error.message}`);
    process.exitCode = 1;
  } finally {
    await close(server).catch(() => {});
    await pool.end().catch(() => {});
  }
}

run();
