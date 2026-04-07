import React from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { logout, me } from '../../services/authApi';

export default function ProfilePage() {
  const navigate = useNavigate();
  const { data, error, isLoading, refetch } = useQuery({ queryKey: ['auth-me'], queryFn: me });

  async function handleLogout() {
    await logout();
    navigate('/login');
  }

  return (
    <div className="page-wrap">
      <div className="flex items-center justify-between mb-4">
        <h1 className="section-title">Profile</h1>
        <Link to="/map" className="nav-link">Map</Link>
      </div>

      <div className="glass-panel p-4">
        {isLoading ? <p>Loading...</p> : null}
        {error ? <p className="text-red-400 text-sm">{error.message}</p> : null}
        {data ? (
          <div className="space-y-2 text-sm">
            <p><strong>Username:</strong> {data.username}</p>
            <p><strong>Email:</strong> {data.email}</p>
            <p><strong>Role:</strong> {data.role}</p>
            <p><strong>Level:</strong> {data.level}</p>
            <p><strong>Energy:</strong> {data.energy}</p>
            <p><strong>Language:</strong> {data.preferred_language_code}</p>
            <p><strong>Current Country/City:</strong> {data.current_country_id} / {data.current_city_id}</p>
          </div>
        ) : null}

        <div className="mt-4 flex gap-2">
          <button className="btn-secondary" onClick={() => refetch()}>Refresh</button>
          <button className="btn-primary" onClick={handleLogout}>Logout</button>
        </div>
      </div>
    </div>
  );
}
