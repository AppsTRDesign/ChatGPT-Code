const adminService = require('./admin.service');

async function dashboard(req, res, next) {
  try {
    const data = await adminService.dashboard();
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function moderationUsers(req, res, next) {
  try {
    const data = await adminService.listModerationCandidates(req.query.limit);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function moderationActions(req, res, next) {
  try {
    const data = await adminService.listModerationActions(req.query.limit);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function updateModerationUser(req, res, next) {
  try {
    const data = await adminService.updateUserModerationStatus(
      req.auth.sub,
      req.params.userId,
      req.body.status,
      req.body.reason
    );
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function getBalancing(req, res, next) {
  try {
    const data = await adminService.getBalancingSettings();
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function updateBalancing(req, res, next) {
  try {
    const data = await adminService.upsertBalancingCategory(req.auth.sub, req.params.category, req.body.settings);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function listTranslations(req, res, next) {
  try {
    const data = await adminService.listTranslations(req.query.language, req.query.domain, req.query.limit);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function updateTranslation(req, res, next) {
  try {
    const data = await adminService.upsertTranslationValue(
      req.auth.sub,
      req.params.key,
      req.body.languageCode,
      req.body.value,
      req.body.isApproved
    );
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

module.exports = {
  dashboard,
  moderationUsers,
  moderationActions,
  updateModerationUser,
  getBalancing,
  updateBalancing,
  listTranslations,
  updateTranslation
};
