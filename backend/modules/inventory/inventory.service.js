const pool = require('../../core/db');
const HttpError = require('../../core/http-error');

async function getInventoryByUser(userId) {
  const [inventoryRows] = await pool.query(
    `SELECT id, user_id, max_slots, used_slots, updated_at
     FROM inventories
     WHERE user_id = ?
     LIMIT 1`,
    [Number(userId)]
  );

  if (!inventoryRows.length) throw new HttpError(404, 'Inventory not found');

  const inventory = inventoryRows[0];

  const [itemRows] = await pool.query(
    `SELECT ii.id, ii.quantity, ii.is_equipped, ii.meta_json,
            i.id AS item_id, i.code, i.name, i.item_type, i.rarity, i.base_price
     FROM inventory_items ii
     JOIN items i ON i.id = ii.item_id
     WHERE ii.inventory_id = ?
     ORDER BY i.rarity DESC, i.name ASC`,
    [Number(inventory.id)]
  );

  return {
    ...inventory,
    items: itemRows
  };
}

async function equipItem(userId, inventoryItemId, equip) {
  const [rows] = await pool.query(
    `SELECT ii.id, ii.inventory_id
     FROM inventory_items ii
     JOIN inventories inv ON inv.id = ii.inventory_id
     WHERE ii.id = ? AND inv.user_id = ?
     LIMIT 1`,
    [Number(inventoryItemId), Number(userId)]
  );

  if (!rows.length) throw new HttpError(404, 'Inventory item not found');

  await pool.query(
    `UPDATE inventory_items
     SET is_equipped = ?
     WHERE id = ?`,
    [equip ? 1 : 0, Number(inventoryItemId)]
  );

  return { inventoryItemId: Number(inventoryItemId), isEquipped: Boolean(equip) };
}

module.exports = {
  getInventoryByUser,
  equipItem
};
