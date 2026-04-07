const pool = require('../../core/db');
const HttpError = require('../../core/http-error');

async function listByCountry(countryCode, limit) {
  const safeLimit = Math.max(1, Math.min(200, Number(limit) || 100));

  const [rows] = await pool.query(
    `SELECT r.id, r.name, r.code, r.country_id, r.controlling_country_id, r.population,
            r.strategic_value, r.defense_level, r.infrastructure_level, r.battle_status,
            c.code AS country_code, c.name AS country_name,
            cc.code AS controlling_country_code, cc.name AS controlling_country_name
     FROM regions r
     JOIN countries c ON c.id = r.country_id
     JOIN countries cc ON cc.id = r.controlling_country_id
     WHERE c.code = ?
     ORDER BY r.population DESC
     LIMIT ?`,
    [String(countryCode || '').toUpperCase(), safeLimit]
  );

  return rows;
}

async function getRegionDetail(regionId) {
  const [rows] = await pool.query(
    `SELECT r.*, c.code AS country_code, c.name AS country_name,
            cc.code AS controlling_country_code, cc.name AS controlling_country_name
     FROM regions r
     JOIN countries c ON c.id = r.country_id
     JOIN countries cc ON cc.id = r.controlling_country_id
     WHERE r.id = ?
     LIMIT 1`,
    [Number(regionId)]
  );

  if (!rows.length) throw new HttpError(404, 'Region not found');

  const [cityRows] = await pool.query(
    `SELECT id, name, population, economy_score, infrastructure_score, latitude, longitude
     FROM cities
     WHERE region_id = ?
     ORDER BY population DESC
     LIMIT 30`,
    [Number(regionId)]
  );

  return {
    ...rows[0],
    cities: cityRows
  };
}

module.exports = {
  listByCountry,
  getRegionDetail
};
