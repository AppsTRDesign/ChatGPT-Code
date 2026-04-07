const travelService = require('./travel.service');

async function quote(req, res, next) {
  try {
    const { departureCityId, arrivalCityId } = req.body;
    const data = await travelService.buildQuote(req.auth.sub, departureCityId, arrivalCityId);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function start(req, res, next) {
  try {
    const { departureCityId, arrivalCityId } = req.body;
    const data = await travelService.startTravel(req.auth.sub, departureCityId, arrivalCityId);
    res.status(201).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function active(req, res, next) {
  try {
    const data = await travelService.getActiveTravel(req.auth.sub);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

module.exports = {
  quote,
  start,
  active
};
