const inventoryService = require('./inventory.service');

async function myInventory(req, res, next) {
  try {
    const data = await inventoryService.getInventoryByUser(req.auth.sub);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

async function equip(req, res, next) {
  try {
    const data = await inventoryService.equipItem(req.auth.sub, req.params.inventoryItemId, req.body.equip !== false);
    res.status(200).json({ ok: true, data });
  } catch (error) {
    next(error);
  }
}

module.exports = {
  myInventory,
  equip
};
