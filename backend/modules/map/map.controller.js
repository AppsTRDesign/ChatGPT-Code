const mapService = require('./map.service');

function getWorld(req, res, next) {
  try {
    const payload = mapService.buildWorldPayload();
    res.status(200).json({ ok: true, data: payload });
  } catch (error) {
    next(error);
  }
}

function getCountry(req, res, next) {
  try {
    const card = mapService.getCountryCard(req.params.countryCode);
    res.status(200).json({ ok: true, data: card });
  } catch (error) {
    next(error);
  }
}

function getCity(req, res, next) {
  try {
    const card = mapService.getCityCard(req.query.countryCode, req.query.cityName);
    res.status(200).json({ ok: true, data: card });
  } catch (error) {
    next(error);
  }
}

module.exports = {
  getWorld,
  getCountry,
  getCity
};
