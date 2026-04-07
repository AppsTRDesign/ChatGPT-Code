const fs = require('fs');
const path = require('path');
const HttpError = require('../../core/http-error');

const MAP_READY_PATH = path.resolve(__dirname, '..', '..', 'database', 'world-data', 'map-ready.json');

function readMapFile() {
  const raw = fs.readFileSync(MAP_READY_PATH, 'utf8');
  return JSON.parse(raw);
}

function cityToMarker(country, region, city) {
  return {
    countryCode: country.code,
    countryName: country.name,
    regionCode: region.code,
    regionName: region.name,
    name: city.name,
    latitude: city.latitude,
    longitude: city.longitude,
    population: city.population,
    isCapital: city.isCapital
  };
}

function countryCentroidFromCities(country) {
  const markers = [];
  country.regions.forEach(function (region) {
    region.cities.forEach(function (city) {
      markers.push(cityToMarker(country, region, city));
    });
  });

  if (!markers.length) {
    return { latitude: 0, longitude: 0 };
  }

  const totalLat = markers.reduce(function (acc, city) { return acc + city.latitude; }, 0);
  const totalLng = markers.reduce(function (acc, city) { return acc + city.longitude; }, 0);

  return {
    latitude: Number((totalLat / markers.length).toFixed(6)),
    longitude: Number((totalLng / markers.length).toFixed(6))
  };
}

function buildCountrySummary(country) {
  const centroid = countryCentroidFromCities(country);

  const cities = [];
  country.regions.forEach(function (region) {
    region.cities.forEach(function (city) {
      cities.push(city);
    });
  });

  const population = cities.reduce(function (acc, city) { return acc + (city.population || 0); }, 0);

  return {
    code: country.code,
    iso3: country.iso3,
    name: country.name,
    flagEmoji: country.flagEmoji,
    centroid: centroid,
    playable: true,
    regionCount: country.regions.length,
    cityCount: cities.length,
    population: population,
    economyScore: 55,
    treasury: 5000000,
    president: 'TBD',
    activeWars: 0,
    visaPolicy: 'Mixed',
    workPermitPolicy: 'Regulated'
  };
}

function buildWorldPayload() {
  const mapData = readMapFile();

  const countries = mapData.countries.map(buildCountrySummary);
  const cities = [];

  mapData.countries.forEach(function (country) {
    country.regions.forEach(function (region) {
      region.cities.forEach(function (city) {
        cities.push(cityToMarker(country, region, city));
      });
    });
  });

  return {
    generatedAt: mapData.generatedAt,
    countries: countries,
    cities: cities
  };
}

function getCountryCard(countryCode) {
  const world = buildWorldPayload();
  const country = world.countries.find(function (item) {
    return item.code.toUpperCase() === String(countryCode || '').toUpperCase();
  });

  if (!country) {
    throw new HttpError(404, 'Country not found');
  }

  return {
    ...country,
    actions: ['view_details', 'fly_to_capital', 'diplomacy_info']
  };
}

function getCityCard(countryCode, cityName) {
  const world = buildWorldPayload();

  const city = world.cities.find(function (item) {
    return (
      item.countryCode.toUpperCase() === String(countryCode || '').toUpperCase() &&
      item.name.toLowerCase() === String(cityName || '').toLowerCase()
    );
  });

  if (!city) {
    throw new HttpError(404, 'City not found');
  }

  return {
    ...city,
    governor: 'TBD',
    infrastructure: 50,
    economy: 55,
    safety: 50,
    airportLevel: 1,
    jobs: Math.floor((city.population || 0) * 0.42),
    developmentProjects: 0,
    actions: ['view_details', 'fly_here', 'apply_work_permit', 'move_here_if_allowed']
  };
}

module.exports = {
  buildWorldPayload,
  getCountryCard,
  getCityCard
};
