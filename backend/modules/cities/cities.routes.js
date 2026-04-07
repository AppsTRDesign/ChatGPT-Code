const express = require('express');
const controller = require('./cities.controller');

const router = express.Router();

router.get('/rankings', controller.getCityRankings);
router.get('/:countryCode/:cityName', controller.getCityDetail);

module.exports = router;
