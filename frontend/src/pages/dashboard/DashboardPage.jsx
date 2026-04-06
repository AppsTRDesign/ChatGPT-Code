import React from 'react';
import { Link } from 'react-router-dom';
import { motion } from 'framer-motion';
import { useQuery } from '@tanstack/react-query';
import { fetchCountryRankings } from '../../services/countryApi';
import { fetchCityRankings } from '../../services/cityApi';
import LanguageSwitcher from '../../components/ui/LanguageSwitcher';

function RankingItem({ left, right }) {
  return (
    <li className="flex items-center justify-between rounded-lg border border-slate-700/70 bg-slate-900/55 px-3 py-2">
      <span className="truncate pr-2">{left}</span>
      <span className="text-slate-300 text-xs md:text-sm">{right}</span>
    </li>
  );
}

export default function DashboardPage() {
  const { data: countryRankings } = useQuery({ queryKey: ['country-rankings'], queryFn: () => fetchCountryRankings(10) });
  const { data: cityRankings } = useQuery({ queryKey: ['city-rankings'], queryFn: () => fetchCityRankings(10) });

  return (
    <div className="page-wrap">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="section-title">Rankings Dashboard</h1>
          <p className="text-sm text-slate-400 mt-1">Global güç dengesini ülke ve şehir bazında canlı takip et.</p>
        </div>
        <div className="flex items-center gap-3">
          <LanguageSwitcher />
          <Link to="/map" className="nav-link">Map</Link>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-3 mt-5">
        <div className="glass-panel p-4">
          <p className="text-xs text-slate-400">Top Countries</p>
          <p className="text-2xl font-semibold mt-1">{(countryRankings || []).length}</p>
        </div>
        <div className="glass-panel p-4">
          <p className="text-xs text-slate-400">Top Cities</p>
          <p className="text-2xl font-semibold mt-1">{(cityRankings || []).length}</p>
        </div>
        <div className="glass-panel p-4">
          <p className="text-xs text-slate-400">Phase</p>
          <p className="text-2xl font-semibold mt-1">13 UI Polish</p>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 mt-5">
        <motion.section initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} className="glass-panel p-4">
          <h2 className="font-medium">Ülke Sıralaması</h2>
          <ul className="mt-3 text-sm space-y-2">
            {(countryRankings || []).map((row) => (
              <RankingItem
                key={row.code}
                left={<>{row.rank}. {row.flag_emoji} <Link to={`/country/${row.code}`} className="text-cyan-300 hover:text-cyan-200">{row.name}</Link></>}
                right={`Eco ${row.economy_score}`}
              />
            ))}
          </ul>
        </motion.section>

        <motion.section initial={{ opacity: 0, y: 12 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.08 }} className="glass-panel p-4">
          <h2 className="font-medium">Şehir Sıralaması</h2>
          <ul className="mt-3 text-sm space-y-2">
            {(cityRankings || []).map((row) => (
              <RankingItem
                key={row.id}
                left={<>{row.rank}. <Link to={`/city/${row.country_code}/${encodeURIComponent(row.name)}`} className="text-cyan-300 hover:text-cyan-200">{row.name}</Link></>}
                right={`Eco ${row.economy_score}`}
              />
            ))}
          </ul>
        </motion.section>
      </div>
    </div>
  );
}
