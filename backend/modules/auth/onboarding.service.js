const pool = require('../../core/db');

function normalizeName(value) {
  if (!value) return null;
  return String(value).trim().toLowerCase();
}

async function findCountryByCode(countryCode) {
  if (!countryCode) return null;
  const [rows] = await pool.query('SELECT id, code, name FROM countries WHERE code = ? LIMIT 1', [countryCode.toUpperCase()]);
  return rows[0] || null;
}

async function findCityByNameInCountry(countryId, cityName) {
  if (!countryId || !cityName) return null;
  const [rows] = await pool.query(
    'SELECT id, name, population FROM cities WHERE country_id = ? AND LOWER(name) = ? LIMIT 1',
    [countryId, normalizeName(cityName)]
  );
  return rows[0] || null;
}

async function findFallbackCityInCountry(countryId) {
  const [rows] = await pool.query(
    'SELECT id, name, population FROM cities WHERE country_id = ? ORDER BY population DESC, id ASC LIMIT 1',
    [countryId]
  );
  return rows[0] || null;
}

async function findActiveBalancedCountry() {
  const [rows] = await pool.query(
    `SELECT c.id, c.code, c.name, COUNT(u.id) AS active_users
     FROM countries c
     LEFT JOIN users u ON u.current_country_id = c.id AND u.status = 'active'
     WHERE c.is_playable = 1 AND c.is_active = 1
     GROUP BY c.id
     ORDER BY active_users ASC, c.total_population DESC, c.id ASC
     LIMIT 1`
  );
  return rows[0] || null;
}

async function assignCountryAndCity(geo) {
  const detectedCountryCode = geo.countryCode ? geo.countryCode.toUpperCase() : null;
  const detectedCityName = geo.cityName || null;

  var country = await findCountryByCode(detectedCountryCode);
  var city = null;
  var reason = null;

  if (country) {
    city = await findCityByNameInCountry(country.id, detectedCityName);
    if (city) {
      reason = 'geoip_exact_country_city';
    } else {
      city = await findFallbackCityInCountry(country.id);
      reason = city ? 'geoip_country_fallback_city' : 'geoip_country_without_city';
    }
  } else {
    country = await findActiveBalancedCountry();
    if (!country) {
      return {
        assignedCountryId: null,
        assignedCityId: null,
        assignmentReason: 'no_playable_country'
      };
    }

    city = await findFallbackCityInCountry(country.id);
    reason = city ? 'fallback_playable_country_balanced' : 'fallback_playable_country_without_city';
  }

  return {
    assignedCountryId: country ? country.id : null,
    assignedCityId: city ? city.id : null,
    assignmentReason: reason,
    detectedCountryCode: detectedCountryCode,
    detectedCityName: detectedCityName
  };
}

module.exports = {
  assignCountryAndCity
};
