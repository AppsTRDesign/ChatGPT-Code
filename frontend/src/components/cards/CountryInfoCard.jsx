import React from 'react';
import { motion } from 'framer-motion';

export default function CountryInfoCard({ country, onFlyToCapital, onViewDetails }) {
  if (!country) return null;

  return (
    <motion.div
      initial={{ opacity: 0, y: 12 }}
      animate={{ opacity: 1, y: 0 }}
      className="glass-panel p-4"
    >
      <h3 className="text-lg font-semibold text-white">{country.flagEmoji} {country.name}</h3>
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm text-slate-300 mt-3">
        <div>Nüfus: {country.population.toLocaleString()}</div>
        <div>Ekonomi: {country.economyScore}</div>
        <div>Hazine: ${country.treasury.toLocaleString()}</div>
        <div>Başkan: {country.president}</div>
        <div>Aktif savaş: {country.activeWars}</div>
        <div>Vize: {country.visaPolicy}</div>
        <div>Work permit: {country.workPermitPolicy}</div>
        <div>Şehir sayısı: {country.cityCount}</div>
      </div>
      <div className="mt-4 flex flex-wrap gap-2">
        <button onClick={onViewDetails} className="btn-secondary text-sm">
          View Details
        </button>
        <button onClick={onFlyToCapital} className="btn-primary text-sm">
          Fly to Capital
        </button>
      </div>
    </motion.div>
  );
}
