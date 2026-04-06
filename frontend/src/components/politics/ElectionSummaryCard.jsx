import React from 'react';

export default function ElectionSummaryCard({ election }) {
  if (!election) return null;

  return (
    <div className="glass-panel p-4 text-sm space-y-1">
      <p><strong>Election #{election.id}</strong></p>
      <p>Type: {election.election_type}</p>
      <p>Status: {election.status}</p>
      <p>Country: {election.country_id}</p>
      <p>City: {election.city_id || '-'}</p>
    </div>
  );
}
