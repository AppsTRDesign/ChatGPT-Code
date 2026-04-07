import React, { useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { fetchCountryRankings } from '../../services/countryApi';
import { declareWar, fetchWarList, fetchWarOverview, reinforceBattle, resolveBattle } from '../../services/warApi';

export default function WarPage() {
  const token = typeof localStorage !== 'undefined' ? localStorage.getItem('accessToken') : '';
  const [defenderCountryId, setDefenderCountryId] = useState('');
  const [targetRegionId, setTargetRegionId] = useState('');
  const [energySpend, setEnergySpend] = useState('10');
  const [error, setError] = useState('');
  const [message, setMessage] = useState('');

  const { data: countries } = useQuery({ queryKey: ['war-country-rankings'], queryFn: () => fetchCountryRankings(50) });
  const { data: wars, refetch: refetchWars } = useQuery({ queryKey: ['war-list'], queryFn: fetchWarList });
  const { data: overview, refetch: refetchOverview } = useQuery({
    queryKey: ['war-overview'],
    queryFn: () => fetchWarOverview(token),
    enabled: Boolean(token),
    refetchInterval: 7000
  });

  const countryItems = useMemo(() => countries || [], [countries]);
  const battles = useMemo(() => (overview && overview.battles ? overview.battles : []), [overview]);

  async function handleDeclareWar() {
    setError('');
    setMessage('');
    try {
      const data = await declareWar(token, Number(defenderCountryId), targetRegionId ? Number(targetRegionId) : null);
      setMessage(`War declared. War #${data.warId}, Battle #${data.battleId}`);
      await Promise.all([refetchWars(), refetchOverview()]);
    } catch (e) {
      setError(e.message);
    }
  }

  async function handleReinforce(battleId, side) {
    setError('');
    setMessage('');
    try {
      const data = await reinforceBattle(token, battleId, side, Number(energySpend));
      setMessage(`Reinforced ${side} side. +${data.contributionPower} power`);
      await refetchOverview();
    } catch (e) {
      setError(e.message);
    }
  }

  async function handleResolve(battleId) {
    setError('');
    setMessage('');
    try {
      const data = await resolveBattle(token, battleId);
      setMessage(`Battle resolved. Winner country #${data.winnerCountryId}`);
      await Promise.all([refetchWars(), refetchOverview()]);
    } catch (e) {
      setError(e.message);
    }
  }

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 p-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold">War Center</h1>
        <Link to="/map" className="text-cyan-400">Map</Link>
      </div>

      <p className="text-slate-400 mt-2 text-sm">War actions require `localStorage.accessToken` and active president role.</p>

      <div className="grid md:grid-cols-2 gap-4 mt-5">
        <div className="rounded-xl border border-slate-800 bg-slate-900 p-4">
          <h2 className="font-medium mb-3">Declare War</h2>
          <select value={defenderCountryId} onChange={(e) => setDefenderCountryId(e.target.value)} className="w-full p-3 bg-slate-950 border border-slate-700 rounded-lg mb-3">
            <option value="">Select defender country</option>
            {countryItems.map((country) => (
              <option key={country.id || country.code} value={country.id || ''}>
                {country.name} ({country.code})
              </option>
            ))}
          </select>
          <input
            value={targetRegionId}
            onChange={(e) => setTargetRegionId(e.target.value)}
            placeholder="Target region ID (optional)"
            className="w-full p-3 bg-slate-950 border border-slate-700 rounded-lg mb-3"
          />
          <button onClick={handleDeclareWar} className="px-4 py-2 rounded bg-red-700 hover:bg-red-600">
            Declare War
          </button>
        </div>

        <div className="rounded-xl border border-slate-800 bg-slate-900 p-4">
          <h2 className="font-medium mb-3">Battle Reinforcement</h2>
          <input
            value={energySpend}
            onChange={(e) => setEnergySpend(e.target.value)}
            placeholder="Energy spend"
            className="w-full p-3 bg-slate-950 border border-slate-700 rounded-lg mb-3"
          />
          <p className="text-xs text-slate-400">Aktif battle kartlarında attacker/defender reinforce butonlarını kullanın.</p>
        </div>
      </div>

      {error ? <p className="text-red-400 text-sm mt-4">{error}</p> : null}
      {message ? <p className="text-emerald-400 text-sm mt-4">{message}</p> : null}

      <div className="grid md:grid-cols-2 gap-4 mt-6">
        <div className="rounded-xl border border-slate-800 bg-slate-900 p-4">
          <h2 className="font-medium mb-3">Recent Wars</h2>
          <div className="space-y-2 text-sm">
            {(wars || []).map((war) => (
              <div key={war.id} className="rounded-lg border border-slate-700 p-2">
                <p>War #{war.id} - {war.status}</p>
                <p className="text-slate-400">{war.attackerCountry.code} vs {war.defenderCountry.code}</p>
              </div>
            ))}
            {!wars || wars.length === 0 ? <p className="text-slate-400">No wars found.</p> : null}
          </div>
        </div>

        <div className="rounded-xl border border-slate-800 bg-slate-900 p-4">
          <h2 className="font-medium mb-3">My Country Battles</h2>
          <div className="space-y-3 text-sm">
            {battles.map((battle) => (
              <div key={battle.id} className="rounded-lg border border-slate-700 p-3">
                <p>Battle #{battle.id} ({battle.status}) - War #{battle.war_id}</p>
                <p className="text-slate-400">Region #{battle.target_region_id}</p>
                <p>Power A/D: {battle.attacker_power} / {battle.defender_power}</p>
                <div className="flex flex-wrap gap-2 mt-2">
                  <button onClick={() => handleReinforce(battle.id, 'attacker')} className="px-2 py-1 rounded bg-slate-700">Reinforce Attacker</button>
                  <button onClick={() => handleReinforce(battle.id, 'defender')} className="px-2 py-1 rounded bg-slate-700">Reinforce Defender</button>
                  <button onClick={() => handleResolve(battle.id)} className="px-2 py-1 rounded bg-amber-700">Resolve</button>
                </div>
              </div>
            ))}
            {battles.length === 0 ? <p className="text-slate-400">No battles for your country yet.</p> : null}
          </div>
        </div>
      </div>
    </div>
  );
}
