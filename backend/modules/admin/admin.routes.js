const express = require('express');
const { roleRequired } = require('../../core/auth-middleware');
const controller = require('./admin.controller');

const router = express.Router();

router.get('/dashboard', roleRequired(['admin']), controller.dashboard);

module.exports = router;
