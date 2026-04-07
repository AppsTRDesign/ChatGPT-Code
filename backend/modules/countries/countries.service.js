const pool = require('../../core/db');
const HttpError = require('../../core/http-error');

async function getCountryDetailByCode(code) {
  const [rows] = await pool.query(
    `SELECT c.id, c.code, c.iso3, c.name, c.flag_emoji, c.treasury, c.total_population,
            c.average_happiness, c.military_power, c.economy_score, c.diplomacy_status,
            c.government_type, c.base_tax_rate, c.visa_policy_mode, c.work_permit_policy_mode,
            c.is_playable, c.is_active,
            COUNT(DISTINCT r.id) AS region_count,
            COUNT(DISTINCT ci.id) AS city_count
     FROM countries c
     LEFT JOIN regions r ON r.country_id = c.id
     LEFT JOIN cities ci ON ci.country_id = c.id
     WHERE c.code = ?
     GROUP BY c.id
     LIMIT 1`,
    [String(code || '').toUpperCase()]
  );

  if (!rows[0]) {
    throw new HttpError(404, 'Country not found');
  }

  return rows[0];
}

async function getCountryRankings(limit) {
  const safeLimit = Math.min(Math.max(Number(limit) || 20, 1), 100);

  const [rows] = await pool.query(
    `SELECT c.id, c.code, c.name, c.flag_emoji, c.total_population, c.economy_score,
            c.treasury, c.military_power,
            COUNT(DISTINCT u.id) AS active_citizens,
            COUNT(DISTINCT ci.id) AS city_count,
            COUNT(DISTINCT r.id) AS region_count
     FROM countries c
     LEFT JOIN users u ON u.current_country_id = c.id AND u.status = 'active'
     LEFT JOIN cities ci ON ci.country_id = c.id
     LEFT JOIN regions r ON r.country_id = c.id
     WHERE c.is_active = 1
     GROUP BY c.id
     ORDER BY c.economy_score DESC, c.total_population DESC
     LIMIT ?`,
    [safeLimit]
  );

  return rows.map(function (row, idx) {
    return { rank: idx + 1, ...row };
  });
}

module.exports = {
  getCountryDetailByCode,
  getCountryRankings
};
