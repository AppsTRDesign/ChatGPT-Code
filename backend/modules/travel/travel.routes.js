const express = require('express');
const controller = require('./travel.controller');
const { authRequired } = require('../../core/auth-middleware');

const router = express.Router();

router.post('/quote', authRequired, controller.quote);
router.post('/start', authRequired, controller.start);
router.get('/active', authRequired, controller.active);

module.exports = router;
