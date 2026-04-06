const express = require('express');
const controller = require('./map.controller');

const router = express.Router();

router.get('/world', controller.getWorld);
router.get('/countries/:countryCode', controller.getCountry);
router.get('/cities', controller.getCity);

module.exports = router;
