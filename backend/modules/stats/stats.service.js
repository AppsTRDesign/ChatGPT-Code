const pool = require('../../core/db');

async function getCountryDailyHistory(countryCode, days) {
  const safeDays = Math.min(Math.max(Number(days) || 30, 1), 365);

  const [rows] = await pool.query(
    `SELECT s.snapshot_date, s.population, s.active_citizens, s.average_wealth,
            s.economy_score, s.tax_revenue, s.unemployment_rate, s.military_strength,
            s.happiness, s.war_success_rate, s.city_count, s.region_count,
            s.immigration_inflow, s.emigration_outflow
     FROM country_stats_daily s
     JOIN countries c ON c.id = s.country_id
     WHERE c.code = ?
     ORDER BY s.snapshot_date DESC
     LIMIT ?`,
    [String(countryCode || '').toUpperCase(), safeDays]
  );

  return rows;
}

async function getCityDailyHistory(cityId, days) {
  const safeDays = Math.min(Math.max(Number(days) || 30, 1), 365);

  const [rows] = await pool.query(
    `SELECT snapshot_date, population, active_citizens, development_level, economy_score,
            local_treasury, airport_level, employment_rate, safety_score, healthcare_score,
            education_score, transport_quality, migration_attractiveness,
            current_projects, governor_rating
     FROM city_stats_daily
     WHERE city_id = ?
     ORDER BY snapshot_date DESC
     LIMIT ?`,
    [Number(cityId), safeDays]
  );

  return rows;
}

module.exports = {
  getCountryDailyHistory,
  getCityDailyHistory
};
