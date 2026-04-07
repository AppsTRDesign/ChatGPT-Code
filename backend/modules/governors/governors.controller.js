const governorsService = require('./governors.service');

async function getCityGovernor(req, res, next) {
  try {
    const data = await governorsService.getActiveGovernor(req.params.cityId);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function getCityProjects(req, res, next) {
  try {
    const data = await governorsService.listCityProjects(req.params.cityId);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function createProject(req, res, next) {
  try {
    const data = await governorsService.startProject(req.auth.sub, req.params.cityId, req.body);
    res.status(201).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function addEffort(req, res, next) {
  try {
    const data = await governorsService.addProjectEffort(
      req.auth.sub,
      req.params.cityId,
      req.params.projectId,
      req.body.effortPoints
    );
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

module.exports = {
  getCityGovernor,
  getCityProjects,
  createProject,
  addEffort
};
