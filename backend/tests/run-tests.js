const assert = require('assert');
const balancing = require('../config/balancing');
const { haversineKm } = require('../utils/geo');
const calculators = require('../utils/simulation-calculators');
const { assertActionAllowed, __resetForTests } = require('../core/anti-abuse');

function testGeoDistance() {
  const km = haversineKm(41.0082, 28.9784, 39.9334, 32.8597);
  assert(km > 300 && km < 500, `Istanbul-Ankara distance out of range: ${km}`);
}

function testBalancingConfig() {
  assert(balancing.economy.minTaxRate >= 0, 'minTaxRate must be >= 0');
  assert(balancing.economy.maxTaxRate <= 100, 'maxTaxRate must be <= 100');
  assert(balancing.travel.localSpeedKmh < balancing.travel.flightSpeedKmh, 'local speed must be lower than flight speed');
  assert(balancing.war.minimumContributionPower >= 1, 'minimum war power invalid');
}

function testTravelHelpers() {
  const sameCityType = calculators.computeTravelType({ id: 1, country_id: 10 }, { id: 1, country_id: 10 }, 0);
  assert.strictEqual(sameCityType, 'same_city');

  const localType = calculators.computeTravelType({ id: 1, country_id: 10 }, { id: 2, country_id: 10 }, 60);
  assert.strictEqual(localType, 'local_travel');

  const internationalType = calculators.computeTravelType({ id: 1, country_id: 10 }, { id: 3, country_id: 20 }, 1200);
  assert.strictEqual(internationalType, 'international_flight');

  const duration = calculators.computeDurationSeconds(1200, 'international_flight', 3);
  assert(duration >= balancing.travel.internationalMinSeconds, 'international duration below minimum');

  const cost = calculators.computeTicketCost(500, 'domestic_flight', 2);
  assert(cost > 0, 'ticket cost must be positive');
}

function testAntiAbuse() {
  __resetForTests();

  assert.doesNotThrow(() => assertActionAllowed(42, 'chat_message'));

  let cooldownBlocked = false;
  try {
    assertActionAllowed(42, 'chat_message');
  } catch (error) {
    cooldownBlocked = error && error.status === 429;
  }

  assert(cooldownBlocked, 'expected cooldown block for repeated action');
}

function run() {
  const tests = [
    ['Geo distance sanity', testGeoDistance],
    ['Balancing config sanity', testBalancingConfig],
    ['Travel helper calculations', testTravelHelpers],
    ['Anti-abuse cooldown', testAntiAbuse]
  ];

  const failures = [];

  for (const [name, fn] of tests) {
    try {
      fn();
      console.log(`✔ ${name}`);
    } catch (error) {
      failures.push({ name, error });
      console.error(`✖ ${name}: ${error.message}`);
    }
  }

  if (failures.length) {
    process.exitCode = 1;
    return;
  }

  console.log('All phase-14 checks passed.');
}

run();
