import React from 'react';
import { motion } from 'framer-motion';

export default function CountryInfoCard({ country, onFlyToCapital }) {
  if (!country) return null;

  return (
    <motion.div
      initial={{ opacity: 0, y: 12 }}
      animate={{ opacity: 1, y: 0 }}
      className="rounded-2xl border border-slate-700 bg-slate-900/80 p-4 shadow-xl"
    >
      <h3 className="text-lg font-semibold text-white">{country.flagEmoji} {country.name}</h3>
      <div className="grid grid-cols-2 gap-2 text-sm text-slate-300 mt-3">
        <div>Nüfus: {country.population.toLocaleString()}</div>
        <div>Ekonomi: {country.economyScore}</div>
        <div>Hazine: ${country.treasury.toLocaleString()}</div>
        <div>Başkan: {country.president}</div>
        <div>Aktif savaş: {country.activeWars}</div>
        <div>Vize: {country.visaPolicy}</div>
        <div>Work permit: {country.workPermitPolicy}</div>
        <div>Şehir sayısı: {country.cityCount}</div>
      </div>
      <div className="mt-4">
        <button onClick={onFlyToCapital} className="px-3 py-2 rounded bg-cyan-600 hover:bg-cyan-500 text-white text-sm">
          Fly to Capital
        </button>
      </div>
    </motion.div>
  );
}
