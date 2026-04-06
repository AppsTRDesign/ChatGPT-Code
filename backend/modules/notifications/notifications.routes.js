const express = require('express');
const { authRequired } = require('../../core/auth-middleware');
const controller = require('./notifications.controller');

const router = express.Router();

router.get('/', authRequired, controller.list);
router.patch('/:notificationId/read', authRequired, controller.markRead);
router.post('/demo', authRequired, controller.demo);

module.exports = router;
