const express = require('express');
const controller = require('./countries.controller');

const router = express.Router();

router.get('/rankings', controller.getCountryRankings);
router.get('/:countryCode', controller.getCountryDetail);

module.exports = router;
