import React from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { fetchCountryRankings } from '../../services/countryApi';
import { fetchCityRankings } from '../../services/cityApi';

export default function DashboardPage() {
  const { data: countryRankings } = useQuery({ queryKey: ['country-rankings'], queryFn: () => fetchCountryRankings(10) });
  const { data: cityRankings } = useQuery({ queryKey: ['city-rankings'], queryFn: () => fetchCityRankings(10) });

  return (
    <div className="min-h-screen bg-slate-950 text-slate-200 p-8">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold">Rankings Dashboard</h1>
        <Link to="/map" className="text-cyan-400">Map</Link>
      </div>

      <div className="grid grid-cols-2 gap-6 mt-6">
        <section className="rounded-xl bg-slate-900 border border-slate-800 p-4">
          <h2 className="font-medium">Ülke Sıralaması</h2>
          <ul className="mt-3 text-sm space-y-2">
            {(countryRankings || []).map((row) => (
              <li key={row.code} className="flex justify-between">
                <span>{row.rank}. {row.flag_emoji} <Link to={`/country/${row.code}`} className="text-cyan-400">{row.name}</Link></span>
                <span>Eco {row.economy_score}</span>
              </li>
            ))}
          </ul>
        </section>

        <section className="rounded-xl bg-slate-900 border border-slate-800 p-4">
          <h2 className="font-medium">Şehir Sıralaması</h2>
          <ul className="mt-3 text-sm space-y-2">
            {(cityRankings || []).map((row) => (
              <li key={row.id} className="flex justify-between">
                <span>{row.rank}. <Link to={`/city/${row.country_code}/${encodeURIComponent(row.name)}`} className="text-cyan-400">{row.name}</Link></span>
                <span>Eco {row.economy_score}</span>
              </li>
            ))}
          </ul>
        </section>
      </div>
    </div>
  );
}
