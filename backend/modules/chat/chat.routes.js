const express = require('express');
const { authRequired } = require('../../core/auth-middleware');
const controller = require('./chat.controller');

const router = express.Router();

router.get('/messages', controller.list);
router.post('/messages', authRequired, controller.send);

module.exports = router;
