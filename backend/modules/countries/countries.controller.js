const countriesService = require('./countries.service');

async function getCountryDetail(req, res, next) {
  try {
    const data = await countriesService.getCountryDetailByCode(req.params.countryCode);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function getCountryRankings(req, res, next) {
  try {
    const data = await countriesService.getCountryRankings(req.query.limit);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

module.exports = {
  getCountryDetail,
  getCountryRankings
};
