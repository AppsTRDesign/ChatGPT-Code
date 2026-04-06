const pool = require('../../core/db');
const HttpError = require('../../core/http-error');
const { getIo } = require('../../sockets/socket-state');

function normalizeScopeType(scopeType) {
  const normalized = String(scopeType || '').toLowerCase();
  if (normalized === 'global' || normalized === 'country' || normalized === 'city') return normalized;
  throw new HttpError(400, 'Invalid scopeType. Use global, country or city');
}

async function ensureUser(userId) {
  const [rows] = await pool.query(
    `SELECT id, username, current_country_id, current_city_id
     FROM users
     WHERE id = ?
     LIMIT 1`,
    [Number(userId)]
  );

  if (!rows.length) throw new HttpError(404, 'User not found');
  return rows[0];
}

async function listMessages(scopeType, scopeId, limit) {
  const normalizedScope = normalizeScopeType(scopeType);
  const safeLimit = Math.max(1, Math.min(100, Number(limit) || 50));

  const [rows] = await pool.query(
    `SELECT c.id, c.scope_type, c.scope_id, c.user_id, c.message, c.created_at,
            u.username
     FROM chats c
     LEFT JOIN users u ON u.id = c.user_id
     WHERE c.scope_type = ? AND c.scope_id = ?
     ORDER BY c.id DESC
     LIMIT ?`,
    [normalizedScope, Number(scopeId) || 0, safeLimit]
  );

  return rows.map((row) => ({
    id: Number(row.id),
    scopeType: row.scope_type,
    scopeId: Number(row.scope_id),
    userId: row.user_id ? Number(row.user_id) : null,
    username: row.username || 'system',
    message: row.message,
    createdAt: row.created_at
  }));
}

async function sendMessage(userId, scopeType, scopeId, message) {
  const user = await ensureUser(userId);
  const normalizedScope = normalizeScopeType(scopeType);
  const targetScopeId = Number(scopeId) || 0;

  const content = String(message || '').trim();
  if (!content) throw new HttpError(400, 'Message cannot be empty');
  if (content.length > 600) throw new HttpError(400, 'Message too long (max 600 chars)');

  if (normalizedScope === 'country' && Number(user.current_country_id) !== targetScopeId) {
    throw new HttpError(403, 'User cannot post outside own country chat');
  }

  if (normalizedScope === 'city' && Number(user.current_city_id) !== targetScopeId) {
    throw new HttpError(403, 'User cannot post outside own city chat');
  }

  const [insertResult] = await pool.query(
    `INSERT INTO chats (scope_type, scope_id, user_id, message)
     VALUES (?, ?, ?, ?)`,
    [normalizedScope, targetScopeId, Number(userId), content]
  );

  const chatMessage = {
    id: Number(insertResult.insertId),
    scopeType: normalizedScope,
    scopeId: targetScopeId,
    userId: Number(userId),
    username: user.username,
    message: content,
    createdAt: new Date().toISOString()
  };

  const io = getIo();
  if (io) {
    io.to(`chat:${normalizedScope}:${targetScopeId}`).emit('chat:message', chatMessage);
  }

  return chatMessage;
}

module.exports = {
  listMessages,
  sendMessage
};
