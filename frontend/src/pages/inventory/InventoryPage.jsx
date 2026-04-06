import React from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { equipInventoryItem, fetchMyInventory } from '../../services/inventoryApi';

export default function InventoryPage() {
  const { data, error, isLoading, refetch } = useQuery({ queryKey: ['my-inventory'], queryFn: fetchMyInventory });

  async function toggleEquip(item) {
    await equipInventoryItem(item.id, !item.is_equipped);
    await refetch();
  }

  return (
    <div className="page-wrap">
      <div className="flex items-center justify-between mb-4">
        <h1 className="section-title">Inventory</h1>
        <Link to="/map" className="nav-link">Map</Link>
      </div>

      <div className="glass-panel p-4">
        {isLoading ? <p>Loading...</p> : null}
        {error ? <p className="text-red-400 text-sm">{error.message}</p> : null}
        {data ? (
          <>
            <p className="text-sm text-slate-300 mb-3">Slots: {data.used_slots}/{data.max_slots}</p>
            <div className="space-y-2">
              {(data.items || []).map((item) => (
                <div key={item.id} className="border border-slate-700 rounded-lg p-3 flex items-center justify-between">
                  <div>
                    <p className="font-medium">{item.name} ({item.rarity})</p>
                    <p className="text-xs text-slate-400">x{item.quantity} • {item.item_type}</p>
                  </div>
                  <button className="btn-secondary text-sm" onClick={() => toggleEquip(item)}>
                    {item.is_equipped ? 'Unequip' : 'Equip'}
                  </button>
                </div>
              ))}
            </div>
          </>
        ) : null}
      </div>
    </div>
  );
}
