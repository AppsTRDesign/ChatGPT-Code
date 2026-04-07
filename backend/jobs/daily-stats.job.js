const pool = require('../core/db');

async function runDailyStatsSnapshot() {
  await pool.query(
    `INSERT INTO country_stats_daily
     (country_id, stat_date, population, active_citizens, average_wealth, economy_score, tax_revenue,
      unemployment_rate, military_strength, happiness_score, war_success_rate, city_count, region_count,
      immigration_inflow, emigration_outflow)
     SELECT
      c.id,
      CURDATE(),
      c.total_population,
      (SELECT COUNT(*) FROM users u WHERE u.current_country_id = c.id),
      0,
      c.economy_score,
      0,
      0,
      c.military_power,
      c.average_happiness,
      0,
      (SELECT COUNT(*) FROM cities ci WHERE ci.country_id = c.id),
      (SELECT COUNT(*) FROM regions r WHERE r.country_id = c.id),
      0,
      0
     FROM countries c
     ON DUPLICATE KEY UPDATE
      population = VALUES(population),
      active_citizens = VALUES(active_citizens),
      economy_score = VALUES(economy_score),
      city_count = VALUES(city_count),
      region_count = VALUES(region_count)`
  );

  await pool.query(
    `INSERT INTO city_stats_daily
     (city_id, stat_date, population, active_citizens, development_level, economy_score,
      local_treasury, airport_level, employment_rate, safety_score, healthcare_score,
      education_score, transport_score, migration_attractiveness, active_project_count,
      governor_rating)
     SELECT
      c.id,
      CURDATE(),
      c.population,
      (SELECT COUNT(*) FROM users u WHERE u.current_city_id = c.id),
      c.development_level,
      c.economy_score,
      c.local_treasury,
      c.airport_level,
      c.employment_rate,
      c.safety_score,
      c.healthcare_score,
      c.education_score,
      c.transport_score,
      0,
      (SELECT COUNT(*) FROM city_projects cp WHERE cp.city_id = c.id AND cp.status = 'active'),
      0
     FROM cities c
     ON DUPLICATE KEY UPDATE
      population = VALUES(population),
      active_citizens = VALUES(active_citizens),
      development_level = VALUES(development_level),
      economy_score = VALUES(economy_score),
      local_treasury = VALUES(local_treasury),
      airport_level = VALUES(airport_level),
      employment_rate = VALUES(employment_rate),
      safety_score = VALUES(safety_score),
      healthcare_score = VALUES(healthcare_score),
      education_score = VALUES(education_score),
      transport_score = VALUES(transport_score),
      active_project_count = VALUES(active_project_count)`
  );

  return { ok: true };
}

module.exports = {
  runDailyStatsSnapshot
};
