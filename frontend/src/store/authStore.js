import { create } from 'zustand';

const getToken = () => (typeof localStorage === 'undefined' ? '' : localStorage.getItem('accessToken') || '');

export const useAuthStore = create((set) => ({
  token: getToken(),
  user: null,
  setToken: (token) => {
    if (typeof localStorage !== 'undefined') {
      if (token) localStorage.setItem('accessToken', token);
      else localStorage.removeItem('accessToken');
    }
    set({ token: token || '' });
  },
  setUser: (user) => set({ user: user || null }),
  clearSession: () => {
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('accessToken');
      localStorage.removeItem('refreshToken');
    }
    set({ token: '', user: null });
  }
}));
