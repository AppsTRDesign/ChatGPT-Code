import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import {
  createElection,
  fetchElectionResults,
  fetchElections,
  finalizeElection,
  joinElectionCandidate,
  proposeLaw,
  voteElection,
  voteLaw,
  finalizeLaw
} from '../../services/politicsApi';

export default function ElectionsPage() {
  const token = typeof localStorage !== 'undefined' ? localStorage.getItem('accessToken') : '';
  const [selectedElectionId, setSelectedElectionId] = useState(null);
  const [candidateUserId, setCandidateUserId] = useState('');
  const [error, setError] = useState('');

  const { data: elections, refetch } = useQuery({ queryKey: ['elections-active'], queryFn: () => fetchElections('active') });
  const { data: results } = useQuery({
    queryKey: ['election-results', selectedElectionId],
    queryFn: () => fetchElectionResults(selectedElectionId),
    enabled: Boolean(selectedElectionId)
  });

  async function handleCreateElection() {
    setError('');
    try {
      await createElection(token, {
        electionScope: 'country',
        officeType: 'president',
        countryId: 1
      });
      await refetch();
    } catch (e) {
      setError(e.message);
    }
  }

  async function handleJoin(electionId) {
    setError('');
    try {
      await joinElectionCandidate(token, electionId, { partyName: 'Reform Front', manifesto: 'Modernize country' });
      await refetch();
    } catch (e) {
      setError(e.message);
    }
  }

  async function handleVote(electionId) {
    setError('');
    try {
      await voteElection(token, electionId, Number(candidateUserId));
      setSelectedElectionId(electionId);
    } catch (e) {
      setError(e.message);
    }
  }

  async function handleFinalizeElection(electionId) {
    setError('');
    try {
      await finalizeElection(token, electionId);
      await refetch();
      setSelectedElectionId(electionId);
    } catch (e) {
      setError(e.message);
    }
  }

  async function handleLawDemo() {
    setError('');
    try {
      const law = await proposeLaw(token, {
        countryId: 1,
        title: 'Adjust Tax Rate',
        lawType: 'tax_rate',
        policyValue: 12
      });
      await voteLaw(token, law.id, 'yes');
      await finalizeLaw(token, law.id);
    } catch (e) {
      setError(e.message);
    }
  }

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 p-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-semibold">Politics / Elections</h1>
        <Link to="/map" className="text-cyan-400">Map</Link>
      </div>

      <div className="mt-4 flex gap-2">
        <button onClick={handleCreateElection} className="px-3 py-2 rounded bg-cyan-600">Create Election</button>
        <button onClick={handleLawDemo} className="px-3 py-2 rounded bg-slate-700">Law Demo</button>
      </div>

      {error ? <p className="mt-3 text-sm text-red-400">{error}</p> : null}

      <div className="mt-6 grid grid-cols-2 gap-4">
        <section className="rounded-xl bg-slate-900 border border-slate-800 p-4">
          <h2 className="font-medium">Active Elections</h2>
          <ul className="mt-3 space-y-2 text-sm">
            {(elections || []).map((e) => (
              <li key={e.id} className="p-2 rounded bg-slate-800/60">
                <p>#{e.id} {e.office_type} ({e.election_scope})</p>
                <p className="text-slate-400">{e.country_name || e.city_name}</p>
                <div className="mt-2 flex gap-2">
                  <button onClick={() => setSelectedElectionId(e.id)} className="px-2 py-1 rounded bg-slate-700">Results</button>
                  <button onClick={() => handleJoin(e.id)} className="px-2 py-1 rounded bg-slate-700">Join Candidate</button>
                  <button onClick={() => handleFinalizeElection(e.id)} className="px-2 py-1 rounded bg-slate-700">Finalize</button>
                </div>
              </li>
            ))}
          </ul>
        </section>

        <section className="rounded-xl bg-slate-900 border border-slate-800 p-4">
          <h2 className="font-medium">Election Voting</h2>
          <input
            value={candidateUserId}
            onChange={(ev) => setCandidateUserId(ev.target.value)}
            placeholder="Candidate user id"
            className="mt-3 w-full p-2 rounded bg-slate-800 border border-slate-700"
          />
          <button
            disabled={!selectedElectionId}
            onClick={() => handleVote(selectedElectionId)}
            className="mt-2 px-3 py-2 rounded bg-cyan-600 disabled:opacity-40"
          >
            Vote Selected Election
          </button>

          <h3 className="mt-4 text-sm font-medium">Results</h3>
          <ul className="mt-2 text-sm text-slate-300 space-y-1">
            {(results || []).map((row) => (
              <li key={row.candidate_user_id}>{row.username} - {row.vote_count} votes</li>
            ))}
          </ul>
        </section>
      </div>
    </div>
  );
}
