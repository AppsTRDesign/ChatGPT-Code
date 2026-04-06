import React from 'react';

export default function WarStatusCard({ war }) {
  if (!war) return null;

  return (
    <div className="glass-panel p-3 text-sm">
      <p><strong>War #{war.id}</strong> - {war.status}</p>
      <p>{war.attackerCountry?.code} vs {war.defenderCountry?.code}</p>
      <p>Score A/D: {war.warScore?.attacker} / {war.warScore?.defender}</p>
    </div>
  );
}
