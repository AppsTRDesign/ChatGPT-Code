import React, { useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { fetchCityRankings } from '../../services/cityApi';
import { fetchActiveTravel, fetchTravelQuote, startTravel } from '../../services/travelApi';

export default function TravelPage() {
  const token = typeof localStorage !== 'undefined' ? localStorage.getItem('accessToken') : '';
  const [departureCityId, setDepartureCityId] = useState('');
  const [arrivalCityId, setArrivalCityId] = useState('');
  const [quote, setQuote] = useState(null);
  const [error, setError] = useState('');

  const { data: cityRankings } = useQuery({ queryKey: ['travel-city-list'], queryFn: () => fetchCityRankings(100) });
  const { data: active, refetch: refetchActive } = useQuery({
    queryKey: ['travel-active'],
    queryFn: () => fetchActiveTravel(token),
    refetchInterval: 5000,
    enabled: Boolean(token)
  });

  const cities = useMemo(() => cityRankings || [], [cityRankings]);

  async function handleQuote() {
    setError('');
    try {
      const data = await fetchTravelQuote(token, Number(departureCityId), Number(arrivalCityId));
      setQuote(data);
    } catch (e) {
      setError(e.message);
    }
  }

  async function handleStart() {
    setError('');
    try {
      await startTravel(token, Number(departureCityId), Number(arrivalCityId));
      await refetchActive();
    } catch (e) {
      setError(e.message);
    }
  }

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 p-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold">Travel Center</h1>
        <Link to="/map" className="text-cyan-400">Map</Link>
      </div>

      <p className="text-slate-400 mt-2 text-sm">
        Not: Bu ekran auth token ile çalışır. Demo için `localStorage.accessToken` değeri gereklidir.
      </p>

      <div className="grid grid-cols-2 gap-4 mt-5">
        <select value={departureCityId} onChange={(e) => setDepartureCityId(e.target.value)} className="p-3 bg-slate-900 border border-slate-700 rounded-xl">
          <option value="">Departure city</option>
          {cities.map((city) => (
            <option key={`dep-${city.id}-${city.name}`} value={city.id || ''}>
              {city.name} ({city.country_code})
            </option>
          ))}
        </select>

        <select value={arrivalCityId} onChange={(e) => setArrivalCityId(e.target.value)} className="p-3 bg-slate-900 border border-slate-700 rounded-xl">
          <option value="">Arrival city</option>
          {cities.map((city) => (
            <option key={`arr-${city.id}-${city.name}`} value={city.id || ''}>
              {city.name} ({city.country_code})
            </option>
          ))}
        </select>
      </div>

      <div className="mt-4 flex gap-2">
        <button onClick={handleQuote} className="px-4 py-2 rounded bg-slate-700">Quote</button>
        <button onClick={handleStart} className="px-4 py-2 rounded bg-cyan-600">Start Travel</button>
      </div>

      {error ? <p className="text-red-400 mt-3 text-sm">{error}</p> : null}

      {quote ? (
        <div className="mt-5 p-4 rounded-xl bg-slate-900 border border-slate-800 text-sm">
          <p>Type: {quote.travelType}</p>
          <p>Distance: {quote.distanceKm} km</p>
          <p>Duration: {quote.durationSeconds} sec</p>
          <p>Ticket: ${quote.ticketCost}</p>
          <p>Permit: {quote.permitStatus}</p>
        </div>
      ) : null}

      {active ? (
        <div className="mt-5 p-4 rounded-xl bg-slate-900 border border-slate-800 text-sm">
          <h2 className="font-medium mb-2">Active Travel</h2>
          <p>Status: {active.travelStatus}</p>
          <p>Type: {active.travelType}</p>
          <p>Remaining: {active.remainingSeconds}s</p>
          <p>From #{active.departureCityId} → To #{active.arrivalCityId}</p>
        </div>
      ) : null}
    </div>
  );
}
