const express = require('express');
const controller = require('./stats.controller');

const router = express.Router();

router.get('/countries/:countryCode/history', controller.getCountryHistory);
router.get('/cities/:cityId/history', controller.getCityHistory);

module.exports = router;
