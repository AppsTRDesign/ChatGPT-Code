const crypto = require('crypto');
const pool = require('../../core/db');
const env = require('../../config/env');

function hashToken(token) {
  return crypto.createHash('sha256').update(token).digest('hex');
}

function buildExpiryDate(days) {
  const now = new Date();
  now.setDate(now.getDate() + days);
  return now;
}

async function saveRefreshToken(userId, refreshToken) {
  const tokenHash = hashToken(refreshToken);
  const expiresAt = buildExpiryDate(env.jwt.refreshTtlDays);

  await pool.query(
    `INSERT INTO refresh_tokens (user_id, token_hash, expires_at, is_revoked)
     VALUES (?, ?, ?, 0)`,
    [userId, tokenHash, expiresAt]
  );
}

async function revokeRefreshToken(refreshToken) {
  const tokenHash = hashToken(refreshToken);
  await pool.query('UPDATE refresh_tokens SET is_revoked = 1 WHERE token_hash = ?', [tokenHash]);
}

async function isRefreshTokenActive(refreshToken) {
  const tokenHash = hashToken(refreshToken);
  const [rows] = await pool.query(
    'SELECT id FROM refresh_tokens WHERE token_hash = ? AND is_revoked = 0 AND expires_at > NOW() LIMIT 1',
    [tokenHash]
  );

  return rows.length > 0;
}

module.exports = {
  saveRefreshToken,
  revokeRefreshToken,
  isRefreshTokenActive
};
