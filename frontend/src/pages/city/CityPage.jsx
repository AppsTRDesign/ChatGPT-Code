import React from 'react';
import { Link, useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { fetchCityDetail, fetchCityHistory } from '../../services/cityApi';

export default function CityPage() {
  const { countryCode, cityName } = useParams();

  const { data: city } = useQuery({
    queryKey: ['city-detail', countryCode, cityName],
    queryFn: () => fetchCityDetail(countryCode, cityName)
  });

  const cityId = city?.id;

  const { data: history } = useQuery({
    queryKey: ['city-history', cityId],
    queryFn: () => fetchCityHistory(cityId, 14),
    enabled: Boolean(cityId)
  });

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 p-6">
      <Link to="/map" className="text-cyan-400 text-sm">← Map</Link>
      <h1 className="text-2xl font-semibold mt-2">{city?.name || cityName}</h1>
      <p className="text-slate-400 mt-1">{city?.country_name} / {city?.region_name}</p>

      <div className="grid grid-cols-2 gap-3 mt-4 text-sm">
        <div className="p-3 rounded-xl bg-slate-900 border border-slate-800">Nüfus: {city?.population?.toLocaleString?.() || '-'}</div>
        <div className="p-3 rounded-xl bg-slate-900 border border-slate-800">Ekonomi: {city?.economy_score ?? '-'}</div>
        <div className="p-3 rounded-xl bg-slate-900 border border-slate-800">Altyapı: {city?.infrastructure_score ?? '-'}</div>
        <div className="p-3 rounded-xl bg-slate-900 border border-slate-800">Güvenlik: {city?.safety_score ?? '-'}</div>
        <div className="p-3 rounded-xl bg-slate-900 border border-slate-800">Havalimanı: {city?.airport_level ?? '-'}</div>
        <div className="p-3 rounded-xl bg-slate-900 border border-slate-800">İstihdam: %{city?.employment_rate ?? '-'}</div>
      </div>

      <div className="mt-6 rounded-xl bg-slate-900 border border-slate-800 p-4">
        <h2 className="font-medium">14 Günlük Trend (city_stats_daily)</h2>
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
