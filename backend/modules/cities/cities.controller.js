const citiesService = require('./cities.service');

async function getCityDetail(req, res, next) {
  try {
    const data = await citiesService.getCityDetailByCountryAndName(req.params.countryCode, req.params.cityName);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function getCityRankings(req, res, next) {
  try {
    const data = await citiesService.getCityRankings(req.query.limit);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

module.exports = {
  getCityDetail,
  getCityRankings
};
