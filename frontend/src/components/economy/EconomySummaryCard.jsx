import React from 'react';
import { formatCurrency, formatNumber } from '../../lib/formatters';

export default function EconomySummaryCard({ overview }) {
  if (!overview) return null;

  return (
    <div className="glass-panel p-4 text-sm space-y-1">
      <p><strong>User Cash:</strong> {formatCurrency(overview.user?.cashBalance || 0, 'USD')}</p>
      <p><strong>Energy:</strong> {formatNumber(overview.user?.energy || 0)}</p>
      <p><strong>City Treasury:</strong> {formatCurrency(overview.city?.local_treasury || 0, 'USD')}</p>
      <p><strong>Country Treasury:</strong> {formatCurrency(overview.country?.treasury || 0, 'USD')}</p>
      <p><strong>Tax Rate:</strong> %{overview.taxRate}</p>
    </div>
  );
}
