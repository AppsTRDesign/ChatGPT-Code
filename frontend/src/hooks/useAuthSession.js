import { useEffect } from 'react';
import { useQuery } from '@tanstack/react-query';
import { me } from '../services/authApi';
import { useAuthStore } from '../store/authStore';

export function useAuthSession() {
  const token = useAuthStore((s) => s.token);
  const setUser = useAuthStore((s) => s.setUser);
  const clearSession = useAuthStore((s) => s.clearSession);

  const query = useQuery({
    queryKey: ['session-me', token],
    queryFn: me,
    enabled: Boolean(token),
    retry: false
  });

  useEffect(() => {
    if (query.data) setUser(query.data);
    if (query.error) clearSession();
  }, [query.data, query.error]);

  return query;
}
