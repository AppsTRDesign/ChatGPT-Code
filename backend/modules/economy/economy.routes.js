const express = require('express');
const controller = require('./economy.controller');
const { authRequired } = require('../../core/auth-middleware');

const router = express.Router();

router.get('/jobs', controller.jobs);
router.get('/overview', authRequired, controller.overview);
router.post('/work', authRequired, controller.work);
router.post('/transfer-support', authRequired, controller.transferSupport);

module.exports = router;
