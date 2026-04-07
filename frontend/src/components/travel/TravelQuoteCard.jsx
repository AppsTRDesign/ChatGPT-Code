import React from 'react';

export default function TravelQuoteCard({ quote }) {
  if (!quote) return null;

  return (
    <div className="glass-panel p-4 text-sm">
      <p>Type: {quote.travelType}</p>
      <p>Distance: {quote.distanceKm} km</p>
      <p>Duration: {quote.durationSeconds} sec</p>
      <p>Ticket: ${quote.ticketCost}</p>
      <p>Permit: {quote.permitStatus}</p>
    </div>
  );
}
