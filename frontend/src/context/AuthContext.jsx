import { createContext, useContext, useState, useEffect, useCallback } from 'react';
import { apiFetch } from '../lib/api';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  // On mount: check for existing token and hydrate user
  useEffect(() => {
    const token = localStorage.getItem('pulsedesk_token');
    if (!token) {
      setLoading(false);
      return;
    }

    apiFetch('/api/auth/me')
      .then((res) => res.json())
      .then((data) => setUser(data))
      .catch(() => {
        localStorage.removeItem('pulsedesk_token');
        setUser(null);
      })
      .finally(() => setLoading(false));
  }, []);

  const login = useCallback(async (email, password) => {
    const res = await apiFetch('/api/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });
    const data = await res.json();
    localStorage.setItem('pulsedesk_token', data.token);
    setUser(data.user);
    return data.user;
  }, []);

  const logout = useCallback(async () => {
    try {
      await apiFetch('/api/auth/logout', { method: 'POST' });
    } catch {
      // best-effort — clear local state regardless
    }
    localStorage.removeItem('pulsedesk_token');
    setUser(null);
  }, []);

  return (
    <AuthContext.Provider value={{ user, loading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
