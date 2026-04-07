import React, { useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { useMutation, useQuery } from '@tanstack/react-query';
import {
  fetchAdminDashboard,
  fetchBalancingSettings,
  fetchModerationActions,
  fetchModerationUsers,
  fetchTranslations,
  updateBalancingCategory,
  updateModerationUser,
  updateTranslation
} from '../../services/adminApi';

function JsonTextarea({ value, onChange }) {
  return (
    <textarea
      className="w-full min-h-[180px] rounded border border-slate-700 bg-slate-950 p-2 text-xs"
      value={value}
      onChange={(e) => onChange(e.target.value)}
    />
  );
}

export default function AdminPage() {
  const [tab, setTab] = useState('dashboard');

  const [moderationUserId, setModerationUserId] = useState('');
  const [moderationStatus, setModerationStatus] = useState('muted');
  const [moderationReason, setModerationReason] = useState('');

  const [balancingCategory, setBalancingCategory] = useState('travel');
  const [balancingJson, setBalancingJson] = useState('{}');

  const [translationLanguage, setTranslationLanguage] = useState('en');
  const [translationDomain, setTranslationDomain] = useState('');
  const [translationKey, setTranslationKey] = useState('');
  const [translationValue, setTranslationValue] = useState('');
  const [translationApproved, setTranslationApproved] = useState(true);

  const dashboardQuery = useQuery({ queryKey: ['admin-dashboard'], queryFn: fetchAdminDashboard });
  const moderationUsersQuery = useQuery({ queryKey: ['admin-moderation-users'], queryFn: () => fetchModerationUsers(30) });
  const moderationActionsQuery = useQuery({ queryKey: ['admin-moderation-actions'], queryFn: () => fetchModerationActions(20) });
  const balancingQuery = useQuery({ queryKey: ['admin-balancing'], queryFn: fetchBalancingSettings });
  const translationsQuery = useQuery({
    queryKey: ['admin-translations', translationLanguage, translationDomain],
    queryFn: () => fetchTranslations(translationLanguage, translationDomain || undefined)
  });

  const moderationMutation = useMutation({
    mutationFn: ({ userId, status, reason }) => updateModerationUser(userId, status, reason),
    onSuccess: () => {
      moderationUsersQuery.refetch();
      moderationActionsQuery.refetch();
      setModerationReason('');
    }
  });

  const balancingMutation = useMutation({
    mutationFn: ({ category, settings }) => updateBalancingCategory(category, settings),
    onSuccess: (data) => {
      balancingQuery.refetch();
      const next = data && data[balancingCategory] ? data[balancingCategory] : {};
      setBalancingJson(JSON.stringify(next, null, 2));
    }
  });

  const translationMutation = useMutation({
    mutationFn: ({ key, languageCode, value, isApproved }) => updateTranslation(key, languageCode, value, isApproved),
    onSuccess: () => {
      translationsQuery.refetch();
      setTranslationValue('');
    }
  });

  const balancingPreset = useMemo(() => {
    const settings = balancingQuery.data && balancingQuery.data[balancingCategory] ? balancingQuery.data[balancingCategory] : {};
    return JSON.stringify(settings, null, 2);
  }, [balancingQuery.data, balancingCategory]);

  function fillBalancingPreset() {
    setBalancingJson(balancingPreset);
  }

  function submitBalancing() {
    try {
      const parsed = JSON.parse(balancingJson || '{}');
      balancingMutation.mutate({ category: balancingCategory, settings: parsed });
    } catch (error) {
      alert(`Invalid JSON: ${error.message}`);
    }
  }

  function submitModeration() {
    if (!moderationUserId) {
      alert('Pick a user id first');
      return;
    }

    moderationMutation.mutate({
      userId: Number(moderationUserId),
      status: moderationStatus,
      reason: moderationReason
    });
  }

  function submitTranslation() {
    if (!translationKey || !translationValue) {
      alert('translation key/value required');
      return;
    }

    translationMutation.mutate({
      key: translationKey,
      languageCode: translationLanguage,
      value: translationValue,
      isApproved: translationApproved
    });
  }

  return (
    <div className="page-wrap space-y-4">
      <div className="flex items-center justify-between">
        <h1 className="section-title">Admin Control Center</h1>
        <Link to="/map" className="nav-link">Map</Link>
      </div>

      <div className="glass-panel p-3 flex flex-wrap gap-2 text-sm">
        {[
          ['dashboard', 'Dashboard'],
          ['moderation', 'Moderation'],
          ['balancing', 'Balancing Sliders'],
          ['i18n', 'i18n Live Edit']
        ].map(([k, label]) => (
          <button key={k} className={`px-3 py-1 rounded ${tab === k ? 'bg-cyan-600 text-white' : 'bg-slate-800 text-slate-300'}`} onClick={() => setTab(k)}>{label}</button>
        ))}
      </div>

      {tab === 'dashboard' ? (
        <div className="glass-panel p-4">
          {dashboardQuery.isLoading ? <p>Loading...</p> : null}
          {dashboardQuery.error ? <p className="text-red-400 text-sm">{dashboardQuery.error.message}</p> : null}
          {dashboardQuery.data ? (
            <div className="grid md:grid-cols-2 gap-3 text-sm">
              <div>Total Users: {dashboardQuery.data.users.total_users}</div>
              <div>Total Countries: {dashboardQuery.data.countries.total_countries}</div>
              <div>Playable Countries: {dashboardQuery.data.countries.playable_countries}</div>
              <div>Total Cities: {dashboardQuery.data.cities.total_cities}</div>
              <div>Active Wars: {dashboardQuery.data.wars.active_wars}</div>
              <div>Active Travels: {dashboardQuery.data.travels.active_travels}</div>
            </div>
          ) : null}
        </div>
      ) : null}

      {tab === 'moderation' ? (
        <div className="grid lg:grid-cols-2 gap-4">
          <div className="glass-panel p-4 space-y-3">
            <h2 className="font-semibold">User moderation</h2>
            <select className="w-full rounded border border-slate-700 bg-slate-950 p-2" value={moderationUserId} onChange={(e) => setModerationUserId(e.target.value)}>
              <option value="">Select user</option>
              {(moderationUsersQuery.data || []).map((u) => (
                <option key={u.id} value={u.id}>#{u.id} {u.username} ({u.status}) - chats7d:{u.chat_messages_last7d}</option>
              ))}
            </select>
            <select className="w-full rounded border border-slate-700 bg-slate-950 p-2" value={moderationStatus} onChange={(e) => setModerationStatus(e.target.value)}>
              <option value="active">active</option>
              <option value="muted">muted</option>
              <option value="banned">banned</option>
            </select>
            <input className="w-full rounded border border-slate-700 bg-slate-950 p-2" placeholder="reason" value={moderationReason} onChange={(e) => setModerationReason(e.target.value)} />
            <button className="btn-primary" onClick={submitModeration}>Apply moderation action</button>
            {moderationMutation.error ? <p className="text-red-400 text-xs">{moderationMutation.error.message}</p> : null}
          </div>

          <div className="glass-panel p-4">
            <h2 className="font-semibold mb-2">Recent actions</h2>
            <div className="space-y-2 text-xs max-h-[360px] overflow-auto">
              {(moderationActionsQuery.data || []).map((a) => (
                <div key={a.id} className="border border-slate-800 rounded p-2">
                  <p>#{a.id} - {a.action_type}</p>
                  <p>admin: {a.admin_username || '-'}</p>
                  <p>target: {a.target_username || '-'}</p>
                  <p>reason: {a.reason || '-'}</p>
                </div>
              ))}
            </div>
          </div>
        </div>
      ) : null}

      {tab === 'balancing' ? (
        <div className="glass-panel p-4 space-y-3">
          <h2 className="font-semibold">Balancing sliders (JSON override)</h2>
          <div className="flex gap-2">
            <select className="rounded border border-slate-700 bg-slate-950 p-2" value={balancingCategory} onChange={(e) => setBalancingCategory(e.target.value)}>
              <option value="travel">travel</option>
              <option value="economy">economy</option>
              <option value="war">war</option>
              <option value="antiAbuse">antiAbuse</option>
            </select>
            <button className="btn-secondary" onClick={fillBalancingPreset}>Load current</button>
            <button className="btn-primary" onClick={submitBalancing}>Save</button>
          </div>
          <JsonTextarea value={balancingJson} onChange={setBalancingJson} />
          {balancingMutation.error ? <p className="text-red-400 text-xs">{balancingMutation.error.message}</p> : null}
        </div>
      ) : null}

      {tab === 'i18n' ? (
        <div className="grid lg:grid-cols-2 gap-4">
          <div className="glass-panel p-4 space-y-3">
            <h2 className="font-semibold">Live translation edit</h2>
            <div className="flex gap-2">
              <input className="rounded border border-slate-700 bg-slate-950 p-2 w-32" value={translationLanguage} onChange={(e) => setTranslationLanguage(e.target.value)} placeholder="lang" />
              <input className="rounded border border-slate-700 bg-slate-950 p-2 flex-1" value={translationDomain} onChange={(e) => setTranslationDomain(e.target.value)} placeholder="domain (optional)" />
            </div>
            <input className="w-full rounded border border-slate-700 bg-slate-950 p-2" value={translationKey} onChange={(e) => setTranslationKey(e.target.value)} placeholder="translation key (e.g. travel.quote_button)" />
            <textarea className="w-full min-h-[140px] rounded border border-slate-700 bg-slate-950 p-2" value={translationValue} onChange={(e) => setTranslationValue(e.target.value)} placeholder="translated value" />
            <label className="text-sm flex items-center gap-2">
              <input type="checkbox" checked={translationApproved} onChange={(e) => setTranslationApproved(e.target.checked)} /> approved
            </label>
            <button className="btn-primary" onClick={submitTranslation}>Save translation</button>
            {translationMutation.error ? <p className="text-red-400 text-xs">{translationMutation.error.message}</p> : null}
          </div>

          <div className="glass-panel p-4">
            <h2 className="font-semibold mb-2">Current values</h2>
            <div className="space-y-2 text-xs max-h-[360px] overflow-auto">
              {(translationsQuery.data || []).slice(0, 80).map((item) => (
                <div key={`${item.key}-${item.language_code}`} className="border border-slate-800 rounded p-2">
                  <p className="font-medium">{item.key}</p>
                  <p className="text-slate-400">domain: {item.domain || '-'}</p>
                  <p>{item.value || '-'}</p>
                </div>
              ))}
            </div>
          </div>
        </div>
      ) : null}
    </div>
  );
}
