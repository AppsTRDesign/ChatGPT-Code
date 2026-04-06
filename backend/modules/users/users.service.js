const pool = require('../../core/db');
const HttpError = require('../../core/http-error');
const { normalizeLang } = require('../i18n/i18n.service');

async function getMyProfile(userId) {
  const [rows] = await pool.query(
    `SELECT u.id, u.email, u.username, u.level, u.experience, u.energy,
            u.preferred_language_code, u.current_country_id, u.current_city_id,
            up.detected_country, up.detected_city, up.assigned_country_id, up.assigned_city_id, up.assignment_reason
     FROM users u
     LEFT JOIN user_profiles up ON up.user_id = u.id
     WHERE u.id = ?
     LIMIT 1`,
    [userId]
  );

  if (!rows[0]) {
    throw new HttpError(404, 'User not found');
  }

  return rows[0];
}

async function updateLanguagePreference(userId, languageCode, enabledCodes) {
  const normalized = normalizeLang(languageCode);
  if (!normalized || !enabledCodes.includes(normalized)) {
    throw new HttpError(400, 'Unsupported language');
  }

  await pool.query('UPDATE users SET preferred_language_code = ? WHERE id = ?', [normalized, userId]);
  return { preferredLanguageCode: normalized };
}

module.exports = {
  getMyProfile,
  updateLanguagePreference
};
