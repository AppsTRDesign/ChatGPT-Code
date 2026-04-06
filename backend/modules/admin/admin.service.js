const pool = require('../../core/db');

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

module.exports = {
  dashboard
};
