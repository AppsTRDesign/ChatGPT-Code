const env = require('../../config/env');

async function resolveGeoLocationFromRequest(req) {
  if (env.geoipProvider === 'mock') {
    return {
      countryCode: env.geoipMockCountryCode,
      countryName: null,
      cityName: env.geoipMockCity,
      confidence: 80,
      provider: 'mock'
    };
  }

  return {
    countryCode: null,
    countryName: null,
    cityName: null,
    confidence: 0,
    provider: 'unknown'
  };
}

module.exports = {
  resolveGeoLocationFromRequest
};
