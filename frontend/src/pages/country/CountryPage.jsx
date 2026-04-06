import React from 'react';
import { Link, useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { fetchCountryDetail, fetchCountryHistory } from '../../services/countryApi';

export default function CountryPage() {
  const { countryCode } = useParams();

  const { data: country } = useQuery({
    queryKey: ['country-detail', countryCode],
    queryFn: () => fetchCountryDetail(countryCode)
  });

  const { data: history } = useQuery({
    queryKey: ['country-history', countryCode],
    queryFn: () => fetchCountryHistory(countryCode, 14)
  });

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 p-6">
      <Link to="/map" className="text-cyan-400 text-sm">← Map</Link>
      <h1 className="text-2xl font-semibold mt-2">{country?.flag_emoji} {country?.name || countryCode}</h1>

      <div className="grid grid-cols-2 gap-3 mt-4 text-sm">
        <div className="p-3 rounded-xl bg-slate-900 border border-slate-800">Nüfus: {country?.total_population?.toLocaleString?.() || '-'}</div>
        <div className="p-3 rounded-xl bg-slate-900 border border-slate-800">Ekonomi: {country?.economy_score ?? '-'}</div>
        <div className="p-3 rounded-xl bg-slate-900 border border-slate-800">Hazine: ${country?.treasury?.toLocaleString?.() || '-'}</div>
        <div className="p-3 rounded-xl bg-slate-900 border border-slate-800">Askeri Güç: {country?.military_power ?? '-'}</div>
        <div className="p-3 rounded-xl bg-slate-900 border border-slate-800">Bölge Sayısı: {country?.region_count ?? '-'}</div>
        <div className="p-3 rounded-xl bg-slate-900 border border-slate-800">Şehir Sayısı: {country?.city_count ?? '-'}</div>
      </div>

      <div className="mt-6 rounded-xl bg-slate-900 border border-slate-800 p-4">
        <h2 className="font-medium">14 Günlük Trend (country_stats_daily)</h2>
        <ul className="mt-2 text-sm text-slate-300 space-y-2">
          {(history || []).map((row) => (
            <li key={row.snapshot_date} className="flex justify-between border-b border-slate-800 pb-2">
              <span>{row.snapshot_date}</span>
              <span>Eco: {row.economy_score} | Pop: {row.population?.toLocaleString?.()}</span>
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
}
