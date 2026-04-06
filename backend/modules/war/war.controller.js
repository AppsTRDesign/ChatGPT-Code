const warService = require('./war.service');

async function list(req, res, next) {
  try {
    const data = await warService.listWars();
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function declare(req, res, next) {
  try {
    const data = await warService.declareWar(req.auth.sub, req.body.defenderCountryId, req.body.targetRegionId);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function reinforce(req, res, next) {
  try {
    const data = await warService.reinforceBattle(req.auth.sub, req.params.battleId, req.body.side, req.body.energySpend);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function resolve(req, res, next) {
  try {
    const data = await warService.resolveBattle(req.auth.sub, req.params.battleId);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function overview(req, res, next) {
  try {
    const data = await warService.warOverview(req.auth.sub);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

module.exports = {
  list,
  declare,
  reinforce,
  resolve,
  overview
};
