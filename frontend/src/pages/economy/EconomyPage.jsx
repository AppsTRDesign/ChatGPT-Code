import React, { useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { fetchEconomyOverview, fetchJobs, performWork, transferSupport } from '../../services/economyApi';

export default function EconomyPage() {
  const token = typeof localStorage !== 'undefined' ? localStorage.getItem('accessToken') : '';
  const [error, setError] = useState('');
  const [workResult, setWorkResult] = useState(null);
  const [supportAmount, setSupportAmount] = useState('5000');

  const { data: overview, refetch: refetchOverview } = useQuery({
    queryKey: ['economy-overview'],
    queryFn: () => fetchEconomyOverview(token),
    enabled: Boolean(token)
  });

  const cityId = overview?.city?.id;
  const countryId = overview?.country?.id;

  const { data: jobs } = useQuery({
    queryKey: ['economy-jobs', cityId, countryId],
    queryFn: () => fetchJobs(cityId, countryId),
    enabled: Boolean(cityId && countryId)
  });

  const recentSessions = useMemo(() => overview?.recentWorkSessions || [], [overview]);

  async function handleWork(jobId) {
    setError('');
    try {
      const result = await performWork(token, jobId);
      setWorkResult(result);
      await refetchOverview();
    } catch (e) {
      setError(e.message);
    }
  }

  async function handleSupportTransfer() {
    setError('');
    try {
      await transferSupport(token, cityId, Number(supportAmount));
      await refetchOverview();
    } catch (e) {
      setError(e.message);
    }
  }

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 p-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold">Economy Center</h1>
        <Link to="/map" className="text-cyan-400">Map</Link>
      </div>

      {!token ? <p className="text-amber-400 mt-3">Bu sayfa için localStorage.accessToken gereklidir.</p> : null}

      <div className="grid grid-cols-3 gap-3 mt-5 text-sm">
        <div className="p-3 rounded-xl bg-slate-900 border border-slate-800">Cash: {overview?.user?.cashBalance ?? '-'}</div>
        <div className="p-3 rounded-xl bg-slate-900 border border-slate-800">City Treasury: {overview?.city?.local_treasury ?? '-'}</div>
        <div className="p-3 rounded-xl bg-slate-900 border border-slate-800">Country Treasury: {overview?.country?.treasury ?? '-'}</div>
        <div className="p-3 rounded-xl bg-slate-900 border border-slate-800">Tax Rate: %{overview?.taxRate ?? '-'}</div>
        <div className="p-3 rounded-xl bg-slate-900 border border-slate-800">Energy: {overview?.user?.energy ?? '-'}</div>
        <div className="p-3 rounded-xl bg-slate-900 border border-slate-800">Level: {overview?.user?.level ?? '-'}</div>
      </div>

      <div className="mt-6 rounded-xl bg-slate-900 border border-slate-800 p-4">
        <h2 className="font-medium">Jobs</h2>
        <ul className="mt-3 space-y-2 text-sm">
          {(jobs || []).map((job) => (
            <li key={job.id} className="p-3 rounded bg-slate-800/70 flex items-center justify-between">
              <span>{job.title} - Salary {job.base_salary} - Energy {job.energy_cost}</span>
              <button onClick={() => handleWork(job.id)} className="px-3 py-1 rounded bg-cyan-600">Work</button>
            </li>
          ))}
        </ul>
      </div>

      {workResult ? (
        <div className="mt-4 rounded-xl bg-slate-900 border border-slate-800 p-4 text-sm">
          <p>Gross: {workResult.grossSalary}</p>
          <p>Tax: {workResult.taxAmount} (%{workResult.taxRate})</p>
          <p>Net: {workResult.netSalary}</p>
          <p>City Share: {workResult.cityShare} | Country Share: {workResult.countryShare}</p>
        </div>
      ) : null}

      <div className="mt-6 rounded-xl bg-slate-900 border border-slate-800 p-4">
        <h2 className="font-medium">Country Support Transfer (President)</h2>
        <div className="mt-3 flex gap-2">
          <input value={supportAmount} onChange={(e) => setSupportAmount(e.target.value)} className="p-2 rounded bg-slate-800 border border-slate-700" />
          <button onClick={handleSupportTransfer} className="px-3 py-2 rounded bg-slate-700">Transfer</button>
        </div>
      </div>

      {error ? <p className="text-red-400 text-sm mt-3">{error}</p> : null}

      <div className="mt-6 rounded-xl bg-slate-900 border border-slate-800 p-4">
        <h2 className="font-medium">Recent Work Sessions</h2>
        <ul className="mt-2 text-sm space-y-1 text-slate-300">
          {recentSessions.map((s) => (
            <li key={s.id}>#{s.id} Net {s.net_salary} / Tax {s.tax_amount} / {s.performed_at}</li>
          ))}
        </ul>
      </div>
    </div>
  );
}
