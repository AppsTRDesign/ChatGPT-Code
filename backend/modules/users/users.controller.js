const usersService = require('./users.service');
const { getEnabledLanguageCodes } = require('../i18n/i18n.service');

async function me(req, res, next) {
  try {
    const result = await usersService.getMyProfile(req.auth.sub);
    res.status(200).json({ ok: true, data: result });
  } catch (error) {
    next(error);
  }
}

async function updateMyLanguage(req, res, next) {
  try {
    const enabledCodes = await getEnabledLanguageCodes();
    const result = await usersService.updateLanguagePreference(
      req.auth.sub,
      req.body.languageCode,
      enabledCodes
    );
    res.status(200).json({ ok: true, data: result });
  } catch (error) {
    next(error);
  }
}

module.exports = {
  me,
  updateMyLanguage
};
