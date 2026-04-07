import { apiGet, apiPatch } from './apiClient';

export function fetchMyInventory() {
  return apiGet('/inventory/me');
}

export function equipInventoryItem(inventoryItemId, equip) {
  return apiPatch(`/inventory/items/${inventoryItemId}/equip`, { equip: Boolean(equip) });
}
