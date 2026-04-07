const express = require('express');
const controller = require('./war.controller');
const { authRequired } = require('../../core/auth-middleware');

const router = express.Router();

router.get('/wars', controller.list);
router.get('/overview', authRequired, controller.overview);
router.post('/declare', authRequired, controller.declare);
router.post('/battles/:battleId/reinforce', authRequired, controller.reinforce);
router.post('/battles/:battleId/resolve', authRequired, controller.resolve);

module.exports = router;
