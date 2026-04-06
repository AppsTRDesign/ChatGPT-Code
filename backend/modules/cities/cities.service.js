const pool = require('../../core/db');
const HttpError = require('../../core/http-error');

async function getCityDetailByCountryAndName(countryCode, cityName) {
  const [rows] = await pool.query(
    `SELECT ci.id, ci.name, ci.population, ci.economy_score, ci.infrastructure_score,
            ci.safety_score, ci.education_score, ci.healthcare_score, ci.transport_score,
            ci.local_treasury, ci.development_level, ci.housing_level, ci.employment_rate,
            ci.airport_level, ci.industry_level,
            c.code AS country_code, c.name AS country_name,
            r.code AS region_code, r.name AS region_name
     FROM cities ci
     JOIN countries c ON c.id = ci.country_id
     JOIN regions r ON r.id = ci.region_id
     WHERE c.code = ? AND LOWER(ci.name) = ?
     LIMIT 1`,
    [String(countryCode || '').toUpperCase(), String(cityName || '').toLowerCase()]
  );

  if (!rows[0]) {
    throw new HttpError(404, 'City not found');
  }

  return rows[0];
}

async function getCityRankings(limit) {
  const safeLimit = Math.min(Math.max(Number(limit) || 20, 1), 100);
  const [rows] = await pool.query(
    `SELECT ci.id, ci.name, ci.population, ci.economy_score, ci.development_level,
            ci.airport_level, ci.employment_rate,
            c.code AS country_code, c.name AS country_name
     FROM cities ci
     JOIN countries c ON c.id = ci.country_id
     ORDER BY ci.economy_score DESC, ci.population DESC
     LIMIT ?`,
    [safeLimit]
  );

  return rows.map(function (row, idx) {
    return { rank: idx + 1, ...row };
  });
}

module.exports = {
  getCityDetailByCountryAndName,
  getCityRankings
};
