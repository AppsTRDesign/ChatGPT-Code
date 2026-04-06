import React from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { fetchAdminDashboard } from '../../services/adminApi';

export default function AdminPage() {
  const { data, error, isLoading, refetch } = useQuery({ queryKey: ['admin-dashboard'], queryFn: fetchAdminDashboard });

  return (
    <div className="page-wrap">
      <div className="flex items-center justify-between mb-4">
        <h1 className="section-title">Admin Dashboard</h1>
        <Link to="/map" className="nav-link">Map</Link>
      </div>

      <div className="glass-panel p-4">
        {isLoading ? <p>Loading...</p> : null}
        {error ? <p className="text-red-400 text-sm">{error.message}</p> : null}
        {data ? (
          <div className="grid md:grid-cols-2 gap-3 text-sm">
            <div>Total Users: {data.users.total_users}</div>
            <div>Total Countries: {data.countries.total_countries}</div>
            <div>Playable Countries: {data.countries.playable_countries}</div>
            <div>Total Cities: {data.cities.total_cities}</div>
            <div>Active Wars: {data.wars.active_wars}</div>
            <div>Active Travels: {data.travels.active_travels}</div>
          </div>
        ) : null}

        <button className="btn-secondary mt-4" onClick={() => refetch()}>Refresh</button>
      </div>
    </div>
  );
}
