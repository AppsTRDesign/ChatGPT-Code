const express = require('express');
const { authRequired } = require('../../core/auth-middleware');
const controller = require('./realtime.controller');

const router = express.Router();

router.get('/snapshot', authRequired, controller.snapshot);
router.post('/publish-demo', authRequired, controller.publishDemo);

module.exports = router;
