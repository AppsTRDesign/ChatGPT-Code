import React from 'react';
import { motion } from 'framer-motion';

export default function CityInfoCard({ city, onFlyHere, onViewDetails }) {
  if (!city) return null;

  return (
    <motion.div
      initial={{ opacity: 0, x: 12 }}
      animate={{ opacity: 1, x: 0 }}
      className="rounded-2xl border border-slate-700 bg-slate-900/80 p-4 shadow-xl"
    >
      <h3 className="text-lg font-semibold text-white">{city.name}</h3>
      <div className="grid grid-cols-2 gap-2 text-sm text-slate-300 mt-3">
        <div>Ülke: {city.countryName}</div>
        <div>Vali: {city.governor}</div>
        <div>Nüfus: {city.population.toLocaleString()}</div>
        <div>Altyapı: {city.infrastructure}</div>
        <div>Ekonomi: {city.economy}</div>
        <div>Güvenlik: {city.safety}</div>
        <div>Havalimanı: {city.airportLevel}</div>
        <div>İş: {city.jobs.toLocaleString()}</div>
      </div>
      <div className="mt-4 flex gap-2">
        <button onClick={onViewDetails} className="px-3 py-2 rounded bg-slate-700 hover:bg-slate-600 text-white text-sm">View Details</button>
        <button onClick={onFlyHere} className="px-3 py-2 rounded bg-cyan-600 hover:bg-cyan-500 text-white text-sm">
          Fly Here
        </button>
        <button className="px-3 py-2 rounded bg-slate-700 hover:bg-slate-600 text-white text-sm">Work Permit</button>
      </div>
    </motion.div>
  );
}
