const express = require('express');
const { authRequired } = require('../../core/auth-middleware');
const controller = require('./inventory.controller');

const router = express.Router();

router.get('/me', authRequired, controller.myInventory);
router.patch('/items/:inventoryItemId/equip', authRequired, controller.equip);

module.exports = router;
