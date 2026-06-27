const BASE_URL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000';

export async function apiFetch(path, options = {}) {
  const token = localStorage.getItem('pulsedesk_token');
  const headers = { ...(options.headers || {}) };

  if (token) {
    headers['Authorization'] = `Bearer ${token}`;
  }

  const method = (options.method || 'GET').toUpperCase();
  if (method === 'POST' || method === 'PUT' || method === 'PATCH') {
    headers['Content-Type'] = 'application/json';
  }

  const response = await fetch(`${BASE_URL}${path}`, {
    ...options,
    headers,
  });

  if (!response.ok) {
    let message = `Request failed (${response.status})`;
    let fieldErrors = null;
    try {
      const data = await response.json();
      if (data.message) message = data.message;
      if (data.errors) fieldErrors = data.errors;
    } catch {
      // response had no JSON body
    }
    const error = new Error(message);
    error.status = response.status;
    if (fieldErrors) error.errors = fieldErrors;
    throw error;
  }

  return response;
}
