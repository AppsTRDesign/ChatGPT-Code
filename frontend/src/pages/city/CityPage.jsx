import React, { useMemo, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { fetchCityDetail, fetchCityHistory } from '../../services/cityApi';
import { contributeProjectEffort, fetchCityGovernor, fetchCityProjects, startCityProject } from '../../services/governorApi';

const PROJECT_TYPES = [
  'infrastructure_upgrade',
  'airport_upgrade',
  'healthcare_upgrade',
  'education_upgrade',
  'industry_upgrade',
  'housing_upgrade',
  'security_upgrade',
  'transport_upgrade'
];

export default function CityPage() {
  const { countryCode, cityName } = useParams();
  const token = typeof localStorage !== 'undefined' ? localStorage.getItem('accessToken') : '';

  const [projectType, setProjectType] = useState(PROJECT_TYPES[0]);
  const [projectBudget, setProjectBudget] = useState('10000');
  const [projectTitle, setProjectTitle] = useState('New city project');
  const [error, setError] = useState('');

  const { data: city, refetch: refetchCity } = useQuery({
    queryKey: ['city-detail', countryCode, cityName],
    queryFn: () => fetchCityDetail(countryCode, cityName)
  });

  const cityId = city?.id;

  const { data: history } = useQuery({
    queryKey: ['city-history', cityId],
    queryFn: () => fetchCityHistory(cityId, 14),
    enabled: Boolean(cityId)
  });

  const { data: governor } = useQuery({
    queryKey: ['city-governor', cityId],
    queryFn: () => fetchCityGovernor(cityId),
    enabled: Boolean(cityId)
  });

  const { data: projects, refetch: refetchProjects } = useQuery({
    queryKey: ['city-projects', cityId],
    queryFn: () => fetchCityProjects(cityId),
    enabled: Boolean(cityId)
  });

  const inProgressProjects = useMemo(
    () => (projects || []).filter((p) => p.status === 'in_progress'),
    [projects]
  );

  async function handleCreateProject() {
    if (!cityId) return;
    setError('');

    try {
      await startCityProject(token, cityId, {
        projectType,
        budgetAllocated: Number(projectBudget),
        title: projectTitle,
        description: `${projectType} project`
      });

      await Promise.all([refetchProjects(), refetchCity()]);
    } catch (e) {
      setError(e.message);
    }
  }

  async function handleEffort(projectId) {
    if (!cityId) return;
    setError('');

    try {
      await contributeProjectEffort(token, cityId, projectId, 8);
      await Promise.all([refetchProjects(), refetchCity()]);
    } catch (e) {
      setError(e.message);
    }
  }

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
        <h2 className="font-medium">Aktif Governor</h2>
        {governor ? (
          <p className="text-sm text-slate-300 mt-2">
            {governor.username} | Reputation: {governor.reputation} | XP: {governor.experience} | Completed: {governor.completed_projects}
          </p>
        ) : (
          <p className="text-sm text-slate-400 mt-2">Bu şehirde aktif governor yok.</p>
        )}
      </div>

      <div className="mt-6 rounded-xl bg-slate-900 border border-slate-800 p-4">
        <h2 className="font-medium">City Development Projects</h2>

        <div className="grid grid-cols-3 gap-3 mt-3">
          <select value={projectType} onChange={(e) => setProjectType(e.target.value)} className="p-2 rounded bg-slate-800 border border-slate-700">
            {PROJECT_TYPES.map((type) => (
              <option key={type} value={type}>{type}</option>
            ))}
          </select>
          <input value={projectTitle} onChange={(e) => setProjectTitle(e.target.value)} className="p-2 rounded bg-slate-800 border border-slate-700" />
          <input value={projectBudget} onChange={(e) => setProjectBudget(e.target.value)} className="p-2 rounded bg-slate-800 border border-slate-700" />
        </div>

        <button onClick={handleCreateProject} className="mt-3 px-4 py-2 rounded bg-cyan-600">Start Project</button>

        {error ? <p className="text-red-400 text-sm mt-2">{error}</p> : null}

        <ul className="mt-4 text-sm space-y-2">
          {(projects || []).map((project) => (
            <li key={project.id} className="p-3 rounded bg-slate-800/70 border border-slate-700">
              <div className="flex items-center justify-between">
                <div>
                  <p className="font-medium">{project.title}</p>
                  <p className="text-slate-400">{project.project_type} | {project.status}</p>
                </div>
                <button
                  onClick={() => handleEffort(project.id)}
                  disabled={project.status !== 'in_progress'}
                  className="px-3 py-1 rounded bg-slate-700 disabled:opacity-30"
                >
                  + Effort
                </button>
              </div>
              <p className="mt-1 text-slate-300">Progress: %{project.progress_percent}</p>
            </li>
          ))}
        </ul>

        {inProgressProjects.length === 0 ? <p className="text-slate-400 text-sm mt-3">Aktif proje yok.</p> : null}
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
