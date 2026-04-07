const regionsService = require('./regions.service');

async function list(req, res, next) {
  try {
    const data = await regionsService.listByCountry(req.query.countryCode, req.query.limit);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function detail(req, res, next) {
  try {
    const data = await regionsService.getRegionDetail(req.params.regionId);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

module.exports = {
  list,
  detail
};
