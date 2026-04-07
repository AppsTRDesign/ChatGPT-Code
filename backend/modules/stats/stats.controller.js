const statsService = require('./stats.service');

async function getCountryHistory(req, res, next) {
  try {
    const data = await statsService.getCountryDailyHistory(req.params.countryCode, req.query.days);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function getCityHistory(req, res, next) {
  try {
    const data = await statsService.getCityDailyHistory(req.params.cityId, req.query.days);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

module.exports = {
  getCountryHistory,
  getCityHistory
};
