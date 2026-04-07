import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { login } from '../../services/authApi';

export default function LoginPage() {
  const navigate = useNavigate();
  const [identity, setIdentity] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');

  async function handleSubmit(e) {
    e.preventDefault();
    setError('');
    try {
      await login(identity, password);
      navigate('/map');
    } catch (err) {
      setError(err.message);
    }
  }

  return (
    <div className="page-wrap flex items-center justify-center">
      <form onSubmit={handleSubmit} className="glass-panel p-6 w-full max-w-md space-y-3">
        <h1 className="section-title">Login</h1>
        <input className="w-full rounded border border-slate-700 bg-slate-950 p-2" value={identity} onChange={(e) => setIdentity(e.target.value)} placeholder="email or username" />
        <input type="password" className="w-full rounded border border-slate-700 bg-slate-950 p-2" value={password} onChange={(e) => setPassword(e.target.value)} placeholder="password" />
        {error ? <p className="text-red-400 text-sm">{error}</p> : null}
        <button className="btn-primary w-full" type="submit">Sign in</button>
      </form>
    </div>
  );
}
