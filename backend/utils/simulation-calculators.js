const balancing = require('../config/balancing');

function clampTaxRate(raw) {
  return Math.min(Math.max(Number(raw || 0), balancing.economy.minTaxRate), balancing.economy.maxTaxRate);
}

function computeIndustryMultiplier(industryLevel) {
  return 1 + (Number(industryLevel || 1) - 1) * balancing.economy.industryLevelMultiplierStep;
}

function computeEmploymentMultiplier(employmentRate) {
  return balancing.economy.employmentBaseMultiplier + Number(employmentRate || 50) / 100;
}

function computeTravelType(fromCity, toCity, distanceKm) {
  if (fromCity.id === toCity.id) return 'same_city';
  if (fromCity.country_id === toCity.country_id) {
    if (distanceKm < 80) return 'local_travel';
    return 'domestic_flight';
  }
  return 'international_flight';
}

function computeDurationSeconds(distanceKm, travelType, airportLevel) {
  if (travelType === 'same_city') return 0;

  const baseSpeed = travelType === 'local_travel' ? balancing.travel.localSpeedKmh : balancing.travel.flightSpeedKmh;
  const airportBoost = 1 + Math.min(Math.max((airportLevel - 1) * balancing.travel.airportBoostPerLevel, 0), balancing.travel.airportBoostCap);
  const effectiveSpeed = baseSpeed * airportBoost;

  const hours = distanceKm / Math.max(effectiveSpeed, 10);
  const baseSeconds = Math.ceil(hours * 3600);

  if (travelType === 'local_travel') return Math.max(baseSeconds, balancing.travel.localMinSeconds);
  if (travelType === 'domestic_flight') return Math.max(baseSeconds + balancing.travel.domesticBufferSeconds, balancing.travel.domesticMinSeconds);
  return Math.max(baseSeconds + balancing.travel.internationalBufferSeconds, balancing.travel.internationalMinSeconds);
}

function computeTicketCost(distanceKm, travelType, airportLevel) {
  if (travelType === 'same_city') return 0;

  const base = travelType === 'local_travel'
    ? balancing.travel.localCostPerKm
    : travelType === 'domestic_flight'
      ? balancing.travel.domesticCostPerKm
      : balancing.travel.internationalCostPerKm;
  const airportDiscount = Math.min((airportLevel - 1) * balancing.travel.airportDiscountPerLevel, balancing.travel.airportDiscountCap);
  const price = distanceKm * base * (1 - airportDiscount);
  return Number(price.toFixed(2));
}

function computeWarContributionPower(spend, level, reputation) {
  return Math.max(
    balancing.war.minimumContributionPower,
    Math.round(
      Number(spend || 0)
      * (1 + Number(level || 1) * balancing.war.contributionLevelStep)
      * (1 + Number(reputation || 0) / balancing.war.contributionReputationScale)
    )
  );
}

function computeWarScoreDelta(attackerPower, defenderPower) {
  return Math.max(
    balancing.war.resolveBaseScoreDelta,
    Math.round(Math.abs(Number(attackerPower || 0) - Number(defenderPower || 0)) / balancing.war.resolveDiffDivider)
  );
}

module.exports = {
  clampTaxRate,
  computeIndustryMultiplier,
  computeEmploymentMultiplier,
  computeTravelType,
  computeDurationSeconds,
  computeTicketCost,
  computeWarContributionPower,
  computeWarScoreDelta
};
