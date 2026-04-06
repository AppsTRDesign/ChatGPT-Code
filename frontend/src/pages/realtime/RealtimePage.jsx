import React, { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { useQuery } from '@tanstack/react-query';
import { io } from 'socket.io-client';
import { fetchRealtimeSnapshot, publishDemoRealtime } from '../../services/realtimeApi';
import { dispatchDemoNotification, fetchNotifications, markNotificationRead } from '../../services/notificationsApi';
import { fetchChatMessages, sendChatMessage } from '../../services/chatApi';

const SOCKET_BASE_URL = import.meta.env.VITE_SOCKET_URL || 'http://localhost:3001';

export default function RealtimePage() {
  const token = typeof localStorage !== 'undefined' ? localStorage.getItem('accessToken') : '';
  const [events, setEvents] = useState([]);
  const [chatMessage, setChatMessage] = useState('');
  const [chatScopeType, setChatScopeType] = useState('global');
  const [chatScopeId, setChatScopeId] = useState('0');
  const [error, setError] = useState('');

  const { data: snapshot, refetch: refetchSnapshot } = useQuery({
    queryKey: ['realtime-snapshot'],
    queryFn: () => fetchRealtimeSnapshot(token),
    enabled: Boolean(token),
    refetchInterval: 10000
  });

  const { data: notifications, refetch: refetchNotifications } = useQuery({
    queryKey: ['notifications', chatScopeType, chatScopeId],
    queryFn: () => fetchNotifications(token, 30),
    enabled: Boolean(token)
  });

  const { data: chatMessages, refetch: refetchChat } = useQuery({
    queryKey: ['chat-messages', chatScopeType, chatScopeId],
    queryFn: () => fetchChatMessages(chatScopeType, Number(chatScopeId), 40)
  });

  useEffect(() => {
    if (!token) return undefined;

    const socket = io(SOCKET_BASE_URL, {
      transports: ['websocket'],
      auth: { token }
    });

    socket.on('connect', () => {
      socket.emit('subscribe:chat', chatScopeType, Number(chatScopeId));
      if (snapshot && snapshot.countryId) socket.emit('subscribe:country', snapshot.countryId);
      if (snapshot && snapshot.cityId) socket.emit('subscribe:city', snapshot.cityId);
    });

    socket.on('chat:message', (payload) => {
      setEvents((prev) => [{ type: 'chat:message', payload, ts: Date.now() }, ...prev].slice(0, 30));
      refetchChat();
    });

    socket.on('notification:new', (payload) => {
      setEvents((prev) => [{ type: 'notification:new', payload, ts: Date.now() }, ...prev].slice(0, 30));
      refetchNotifications();
    });

    socket.on('realtime:travel', (payload) => setEvents((prev) => [{ type: 'realtime:travel', payload, ts: Date.now() }, ...prev].slice(0, 30)));
    socket.on('realtime:election', (payload) => setEvents((prev) => [{ type: 'realtime:election', payload, ts: Date.now() }, ...prev].slice(0, 30)));
    socket.on('realtime:war', (payload) => setEvents((prev) => [{ type: 'realtime:war', payload, ts: Date.now() }, ...prev].slice(0, 30)));

    return () => socket.disconnect();
  }, [token, chatScopeType, chatScopeId, snapshot && snapshot.countryId, snapshot && snapshot.cityId]);

  const recentEvents = useMemo(() => events.slice(0, 10), [events]);

  async function handleSendMessage() {
    setError('');
    try {
      await sendChatMessage(token, chatScopeType, Number(chatScopeId), chatMessage);
      setChatMessage('');
      await refetchChat();
    } catch (e) {
      setError(e.message);
    }
  }

  async function handlePublishDemo() {
    setError('');
    try {
      await publishDemoRealtime(token);
      await refetchSnapshot();
      await refetchNotifications();
    } catch (e) {
      setError(e.message);
    }
  }

  async function handleDemoNotification() {
    setError('');
    try {
      await dispatchDemoNotification(token);
      await refetchNotifications();
    } catch (e) {
      setError(e.message);
    }
  }

  async function handleMarkRead(notificationId) {
    setError('');
    try {
      await markNotificationRead(token, notificationId);
      await refetchNotifications();
    } catch (e) {
      setError(e.message);
    }
  }

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 p-6">
      <div className="flex items-center justify-between mb-4">
        <h1 className="text-2xl font-semibold">Realtime + Notifications Center</h1>
        <Link to="/map" className="text-cyan-400">Map</Link>
      </div>

      <p className="text-slate-400 text-sm">Bu ekran canlı socket event + chat + bildirim akışını gösterir. Token: <code>localStorage.accessToken</code>.</p>
      {error ? <p className="text-red-400 text-sm mt-3">{error}</p> : null}

      <div className="grid md:grid-cols-2 gap-4 mt-5">
        <div className="rounded-xl border border-slate-800 bg-slate-900 p-4 space-y-3">
          <h2 className="font-medium">Live Snapshot</h2>
          <pre className="text-xs text-slate-300 overflow-auto max-h-48">{JSON.stringify(snapshot || {}, null, 2)}</pre>
          <div className="flex gap-2">
            <button className="px-3 py-2 rounded bg-indigo-700" onClick={handlePublishDemo}>Publish Demo Events</button>
            <button className="px-3 py-2 rounded bg-slate-700" onClick={handleDemoNotification}>Demo Notification</button>
          </div>
        </div>

        <div className="rounded-xl border border-slate-800 bg-slate-900 p-4">
          <h2 className="font-medium mb-2">Realtime Event Feed</h2>
          <div className="space-y-2 text-xs">
            {recentEvents.map((evt) => (
              <div key={`${evt.type}-${evt.ts}`} className="border border-slate-700 rounded p-2">
                <p className="text-cyan-300">{evt.type}</p>
                <pre className="text-slate-300 overflow-auto max-h-24">{JSON.stringify(evt.payload, null, 2)}</pre>
              </div>
            ))}
            {recentEvents.length === 0 ? <p className="text-slate-400">Henüz event yok.</p> : null}
          </div>
        </div>
      </div>

      <div className="grid md:grid-cols-2 gap-4 mt-5">
        <div className="rounded-xl border border-slate-800 bg-slate-900 p-4">
          <h2 className="font-medium mb-3">Chat</h2>
          <div className="grid grid-cols-3 gap-2 mb-2">
            <select value={chatScopeType} onChange={(e) => setChatScopeType(e.target.value)} className="p-2 bg-slate-950 border border-slate-700 rounded">
              <option value="global">Global</option>
              <option value="country">Country</option>
              <option value="city">City</option>
            </select>
            <input value={chatScopeId} onChange={(e) => setChatScopeId(e.target.value)} className="p-2 bg-slate-950 border border-slate-700 rounded" placeholder="scopeId" />
            <button className="p-2 rounded bg-slate-700" onClick={() => refetchChat()}>Refresh</button>
          </div>
          <div className="space-y-2 max-h-56 overflow-auto text-xs mb-3">
            {(chatMessages || []).map((item) => (
              <div key={item.id} className="border border-slate-700 rounded p-2">
                <p className="text-cyan-200">{item.username}</p>
                <p>{item.message}</p>
              </div>
            ))}
          </div>
          <div className="flex gap-2">
            <input value={chatMessage} onChange={(e) => setChatMessage(e.target.value)} placeholder="Mesaj" className="flex-1 p-2 bg-slate-950 border border-slate-700 rounded" />
            <button className="px-3 rounded bg-cyan-700" onClick={handleSendMessage}>Send</button>
          </div>
        </div>

        <div className="rounded-xl border border-slate-800 bg-slate-900 p-4">
          <h2 className="font-medium mb-3">Notifications</h2>
          <div className="space-y-2 max-h-64 overflow-auto text-xs">
            {(notifications || []).map((item) => (
              <div key={item.id} className="border border-slate-700 rounded p-2">
                <p className="text-amber-300">{item.title}</p>
                <p className="text-slate-300">{item.body}</p>
                <p className="text-slate-500">{item.channel} {item.isRead ? '(read)' : '(unread)'}</p>
                {!item.isRead ? (
                  <button className="mt-1 px-2 py-1 rounded bg-slate-700" onClick={() => handleMarkRead(item.id)}>Mark Read</button>
                ) : null}
              </div>
            ))}
            {!notifications || notifications.length === 0 ? <p className="text-slate-400">Bildirim bulunamadı.</p> : null}
          </div>
        </div>
      </div>
    </div>
  );
}
