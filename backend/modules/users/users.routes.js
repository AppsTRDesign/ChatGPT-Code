const express = require('express');
const { authRequired } = require('../../core/auth-middleware');
const controller = require('./users.controller');

const router = express.Router();

router.get('/me', authRequired, controller.me);
router.patch('/me/language', authRequired, controller.updateMyLanguage);

module.exports = router;
