const express = require('express');
const { roleRequired } = require('../../core/auth-middleware');
const controller = require('./admin.controller');

const router = express.Router();

router.get('/dashboard', roleRequired(['admin']), controller.dashboard);

router.get('/moderation/users', roleRequired(['admin']), controller.moderationUsers);
router.get('/moderation/actions', roleRequired(['admin']), controller.moderationActions);
router.patch('/moderation/users/:userId', roleRequired(['admin']), controller.updateModerationUser);

router.get('/balancing/settings', roleRequired(['admin']), controller.getBalancing);
router.put('/balancing/settings/:category', roleRequired(['admin']), controller.updateBalancing);

router.get('/i18n/translations', roleRequired(['admin']), controller.listTranslations);
router.put('/i18n/translations/:key', roleRequired(['admin']), controller.updateTranslation);

module.exports = router;
