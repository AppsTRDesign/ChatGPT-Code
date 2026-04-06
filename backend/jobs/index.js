const { runTravelCompletionJob } = require('./travel-completion.job');
const { runDailyStatsSnapshot } = require('./daily-stats.job');

let intervals = [];

function startJobs() {
  const travelIntervalMs = Number(process.env.JOB_TRAVEL_COMPLETION_MS || 15000);
  const statsIntervalMs = Number(process.env.JOB_DAILY_STATS_MS || 3600000);

  intervals.push(setInterval(() => runTravelCompletionJob().catch((err) => console.error('[job][travel-completion]', err.message)), travelIntervalMs));
  intervals.push(setInterval(() => runDailyStatsSnapshot().catch((err) => console.error('[job][daily-stats]', err.message)), statsIntervalMs));
}

function stopJobs() {
  intervals.forEach((id) => clearInterval(id));
  intervals = [];
}

module.exports = {
  startJobs,
  stopJobs,
  runTravelCompletionJob,
  runDailyStatsSnapshot
};
