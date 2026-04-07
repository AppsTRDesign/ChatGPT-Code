const economyService = require('./economy.service');

async function jobs(req, res, next) {
  try {
    const cityId = Number(req.query.cityId);
    const countryId = Number(req.query.countryId);
    const data = await economyService.getJobsByCity(cityId, countryId);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function work(req, res, next) {
  try {
    const data = await economyService.performWork(req.auth.sub, req.body.jobId);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function overview(req, res, next) {
  try {
    const data = await economyService.overview(req.auth.sub);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function transferSupport(req, res, next) {
  try {
    const data = await economyService.transferCountrySupport(req.auth.sub, req.body.cityId, req.body.amount);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

module.exports = {
  jobs,
  work,
  overview,
  transferSupport
};
