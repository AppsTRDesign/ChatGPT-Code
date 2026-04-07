module.exports = {
  economy: {
    industryLevelMultiplierStep: 0.04,
    employmentBaseMultiplier: 0.7,
    experiencePerWork: 15,
    minTaxRate: 1,
    maxTaxRate: 65,
    treasuryCountryShare: 0.6
  },
  travel: {
    localSpeedKmh: 60,
    flightSpeedKmh: 780,
    airportBoostPerLevel: 0.03,
    airportBoostCap: 0.24,
    localMinSeconds: 300,
    domesticMinSeconds: 1200,
    internationalMinSeconds: 2400,
    domesticBufferSeconds: 1800,
    internationalBufferSeconds: 3600,
    localCostPerKm: 0.15,
    domesticCostPerKm: 0.23,
    internationalCostPerKm: 0.36,
    airportDiscountPerLevel: 0.01,
    airportDiscountCap: 0.08
  },
  war: {
    contributionLevelStep: 0.05,
    contributionReputationScale: 1000,
    minimumContributionPower: 1,
    resolveBaseScoreDelta: 5,
    resolveDiffDivider: 10
  },
  antiAbuse: {
    cooldowns: {
      work: 8,
      chat_message: 2,
      battle_reinforce: 3
    },
    maxActionBurst: {
      chat_message: 5
    }
  }
};
