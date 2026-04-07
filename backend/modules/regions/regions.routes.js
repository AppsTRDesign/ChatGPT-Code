const express = require('express');
const controller = require('./regions.controller');

const router = express.Router();

router.get('/', controller.list);
router.get('/:regionId', controller.detail);

module.exports = router;
