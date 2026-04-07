const pool = require('../../core/db');
const balancingDefaults = require('../../config/balancing');
const HttpError = require('../../core/http-error');

async function dashboard() {
  const [[users]] = await pool.query('SELECT COUNT(*) AS total_users FROM users');
  const [[countries]] = await pool.query('SELECT COUNT(*) AS total_countries, SUM(is_playable = 1) AS playable_countries FROM countries');
  const [[cities]] = await pool.query('SELECT COUNT(*) AS total_cities FROM cities');
  const [[wars]] = await pool.query("SELECT COUNT(*) AS active_wars FROM wars WHERE status IN ('declared','active')");
  const [[travels]] = await pool.query("SELECT COUNT(*) AS active_travels FROM travels WHERE travel_status IN ('scheduled','in_progress')");

  const [topCountries] = await pool.query(
    `SELECT code, name, treasury, economy_score, total_population
     FROM countries
     ORDER BY economy_score DESC
     LIMIT 10`
  );

  return {
    users,
    countries,
    cities,
    wars,
    travels,
    topCountries
  };
}

async function listModerationCandidates(limit) {
  const safeLimit = Math.min(Math.max(Number(limit) || 20, 1), 100);

  const [rows] = await pool.query(
    `SELECT u.id, u.username, u.email, u.status, u.role, u.last_login_at, u.created_at,
            COUNT(c.id) AS chat_messages_last7d
     FROM users u
     LEFT JOIN chats c ON c.user_id = u.id AND c.created_at >= (NOW() - INTERVAL 7 DAY)
     GROUP BY u.id
     ORDER BY chat_messages_last7d DESC, u.created_at DESC
     LIMIT ?`,
    [safeLimit]
  );

  return rows;
}

async function updateUserModerationStatus(adminUserId, userId, status, reason) {
  const nextStatus = String(status || '').toLowerCase();
  const allowed = ['active', 'muted', 'banned'];

  if (!allowed.includes(nextStatus)) {
    throw new HttpError(400, `Invalid status. Allowed: ${allowed.join(', ')}`);
  }

  const targetUserId = Number(userId);
  if (!targetUserId) throw new HttpError(400, 'Invalid user id');

  const [result] = await pool.query('UPDATE users SET status = ? WHERE id = ? LIMIT 1', [nextStatus, targetUserId]);
  if (result.affectedRows === 0) throw new HttpError(404, 'User not found');

  await pool.query(
    `INSERT INTO moderation_actions
     (target_user_id, admin_user_id, action_type, reason, payload_json)
     VALUES (?, ?, 'user_status_update', ?, JSON_OBJECT('new_status', ?))`,
    [targetUserId, Number(adminUserId), reason || null, nextStatus]
  );

  const [[updated]] = await pool.query('SELECT id, username, email, role, status FROM users WHERE id = ? LIMIT 1', [targetUserId]);
  return updated;
}

async function listModerationActions(limit) {
  const safeLimit = Math.min(Math.max(Number(limit) || 20, 1), 200);
  const [rows] = await pool.query(
    `SELECT ma.id, ma.action_type, ma.reason, ma.payload_json, ma.created_at,
            target.id AS target_user_id, target.username AS target_username,
            admin.id AS admin_user_id, admin.username AS admin_username
     FROM moderation_actions ma
     LEFT JOIN users target ON target.id = ma.target_user_id
     LEFT JOIN users admin ON admin.id = ma.admin_user_id
     ORDER BY ma.id DESC
     LIMIT ?`,
    [safeLimit]
  );

  return rows;
}

function getDefaultBalancingSettings() {
  return {
    travel: balancingDefaults.travel,
    economy: balancingDefaults.economy,
    war: balancingDefaults.war,
    antiAbuse: balancingDefaults.antiAbuse
  };
}

async function getBalancingSettings() {
  const defaults = getDefaultBalancingSettings();
  const [rows] = await pool.query('SELECT category, settings_json FROM admin_balancing_settings');

  for (const row of rows) {
    defaults[row.category] = row.settings_json || {};
  }

  return defaults;
}

async function upsertBalancingCategory(adminUserId, category, settings) {
  const safeCategory = String(category || '').trim();
  const allowed = ['travel', 'economy', 'war', 'antiAbuse'];
  if (!allowed.includes(safeCategory)) {
    throw new HttpError(400, `Invalid category. Allowed: ${allowed.join(', ')}`);
  }

  if (!settings || typeof settings !== 'object' || Array.isArray(settings)) {
    throw new HttpError(400, 'settings must be a JSON object');
  }

  await pool.query(
    `INSERT INTO admin_balancing_settings (category, settings_json, updated_by_user_id)
     VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE
      settings_json = VALUES(settings_json),
      updated_by_user_id = VALUES(updated_by_user_id),
      updated_at = CURRENT_TIMESTAMP`,
    [safeCategory, JSON.stringify(settings), Number(adminUserId)]
  );

  await pool.query(
    `INSERT INTO moderation_actions
      (admin_user_id, action_type, reason, payload_json)
     VALUES (?, 'balancing_update', 'balancing slider update', JSON_OBJECT('category', ?))`,
    [Number(adminUserId), safeCategory]
  );

  return getBalancingSettings();
}

async function listTranslations(languageCode, domain, limit) {
  const safeLanguageCode = String(languageCode || 'en').toLowerCase();
  const safeLimit = Math.min(Math.max(Number(limit) || 100, 1), 500);

  const [rows] = await pool.query(
    `SELECT tk.id AS translation_key_id, tk.key, tk.domain, tk.description,
            l.code AS language_code,
            tv.id AS translation_value_id, tv.value, tv.is_approved, tv.updated_at
     FROM translation_keys tk
     JOIN languages l ON l.code = ?
     LEFT JOIN translation_values tv ON tv.translation_key_id = tk.id AND tv.language_id = l.id
     WHERE (? IS NULL OR tk.domain = ?)
     ORDER BY tk.key ASC
     LIMIT ?`,
    [safeLanguageCode, domain || null, domain || null, safeLimit]
  );

  return rows;
}

async function upsertTranslationValue(adminUserId, key, languageCode, value, isApproved) {
  const safeKey = String(key || '').trim();
  const safeLanguageCode = String(languageCode || 'en').toLowerCase();

  if (!safeKey) throw new HttpError(400, 'Missing translation key');
  if (!String(value || '').trim()) throw new HttpError(400, 'Missing translation value');

  const connection = await pool.getConnection();
  try {
    await connection.beginTransaction();

    const [keyRows] = await connection.query('SELECT id FROM translation_keys WHERE `key` = ? LIMIT 1', [safeKey]);
    let keyId = keyRows[0] && keyRows[0].id;

    if (!keyId) {
      const [insertKey] = await connection.query(
        'INSERT INTO translation_keys (`key`, domain, description) VALUES (?, ?, ?)',
        [safeKey, 'admin_live_edit', 'Created from admin live translation editor']
      );
      keyId = insertKey.insertId;
    }

    const [langRows] = await connection.query('SELECT id FROM languages WHERE code = ? LIMIT 1', [safeLanguageCode]);
    if (!langRows[0]) throw new HttpError(404, 'Language not found');
    const languageId = langRows[0].id;

    await connection.query(
      `INSERT INTO translation_values
       (translation_key_id, language_id, value, is_approved, updated_by_user_id)
       VALUES (?, ?, ?, ?, ?)
       ON DUPLICATE KEY UPDATE
        value = VALUES(value),
        is_approved = VALUES(is_approved),
        updated_by_user_id = VALUES(updated_by_user_id),
        updated_at = CURRENT_TIMESTAMP`,
      [Number(keyId), Number(languageId), String(value), isApproved ? 1 : 0, Number(adminUserId)]
    );

    await connection.query(
      `INSERT INTO moderation_actions
       (admin_user_id, action_type, reason, payload_json)
       VALUES (?, 'translation_update', 'live i18n edit', JSON_OBJECT('key', ?, 'language', ?))`,
      [Number(adminUserId), safeKey, safeLanguageCode]
    );

    await connection.commit();
  } catch (error) {
    await connection.rollback();
    throw error;
  } finally {
    connection.release();
  }

  const [rows] = await pool.query(
    `SELECT tk.key, l.code AS language_code, tv.value, tv.is_approved, tv.updated_at
     FROM translation_values tv
     JOIN translation_keys tk ON tk.id = tv.translation_key_id
     JOIN languages l ON l.id = tv.language_id
     WHERE tk.key = ? AND l.code = ?
     LIMIT 1`,
    [safeKey, safeLanguageCode]
  );

  return rows[0] || null;
}

module.exports = {
  dashboard,
  listModerationCandidates,
  updateUserModerationStatus,
  listModerationActions,
  getBalancingSettings,
  upsertBalancingCategory,
  listTranslations,
  upsertTranslationValue
};
