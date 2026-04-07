const pool = require('../../core/db');
const HttpError = require('../../core/http-error');
const { getIo } = require('../../sockets/socket-state');

async function listUserNotifications(userId, limit) {
  const safeLimit = Math.max(1, Math.min(100, Number(limit) || 30));
  const [rows] = await pool.query(
    `SELECT id, channel, title, body, payload_json, is_read, created_at, read_at
     FROM notifications
     WHERE user_id = ?
     ORDER BY id DESC
     LIMIT ?`,
    [Number(userId), safeLimit]
  );

  return rows.map((row) => ({
    id: Number(row.id),
    channel: row.channel,
    title: row.title,
    body: row.body,
    payload: row.payload_json,
    isRead: Boolean(row.is_read),
    createdAt: row.created_at,
    readAt: row.read_at
  }));
}

async function markAsRead(userId, notificationId) {
  const [rows] = await pool.query(
    `SELECT id, user_id
     FROM notifications
     WHERE id = ?
     LIMIT 1`,
    [Number(notificationId)]
  );

  if (!rows.length) throw new HttpError(404, 'Notification not found');
  if (Number(rows[0].user_id) !== Number(userId)) throw new HttpError(403, 'Notification does not belong to user');

  await pool.query(
    `UPDATE notifications
     SET is_read = 1, read_at = NOW()
     WHERE id = ?`,
    [Number(notificationId)]
  );

  return { notificationId: Number(notificationId), isRead: true };
}

async function createAndDispatchUserNotification(userId, channel, title, body, payload) {
  const payloadJson = payload ? JSON.stringify(payload) : null;

  const [insertResult] = await pool.query(
    `INSERT INTO notifications (user_id, channel, title, body, payload_json)
     VALUES (?, ?, ?, ?, ?)`,
    [Number(userId), channel || 'system', title || 'Notification', body || '', payloadJson]
  );

  const notification = {
    id: Number(insertResult.insertId),
    userId: Number(userId),
    channel: channel || 'system',
    title: title || 'Notification',
    body: body || '',
    payload: payload || null,
    isRead: false,
    createdAt: new Date().toISOString()
  };

  const io = getIo();
  if (io) {
    io.to(`user:${Number(userId)}`).emit('notification:new', notification);
  }

  return notification;
}

async function createDemoNotification(userId) {
  const [rows] = await pool.query(
    `SELECT id, current_country_id, current_city_id
     FROM users
     WHERE id = ?
     LIMIT 1`,
    [Number(userId)]
  );

  if (!rows.length) throw new HttpError(404, 'User not found');
  const user = rows[0];

  return createAndDispatchUserNotification(
    Number(userId),
    'system',
    'Realtime test event',
    'Phase 12 demo notification dispatched',
    {
      type: 'demo_notification',
      countryId: user.current_country_id,
      cityId: user.current_city_id
    }
  );
}

module.exports = {
  listUserNotifications,
  markAsRead,
  createAndDispatchUserNotification,
  createDemoNotification
};
