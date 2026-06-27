import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { apiFetch } from '../lib/api';

const PRIORITIES = ['low', 'medium', 'high', 'urgent'];

export default function TicketCreatePage() {
  const navigate = useNavigate();
  const [form, setForm] = useState({
    subject: '',
    description: '',
    priority: 'medium',
    tags: '',
  });
  const [errors, setErrors] = useState({});
  const [submitting, setSubmitting] = useState(false);

  const update = (field) => (e) => {
    setForm((f) => ({ ...f, [field]: e.target.value }));
    setErrors((prev) => ({ ...prev, [field]: null }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    setSubmitting(true);
    try {
      const tags = form.tags
        .split(',')
        .map((t) => t.trim())
        .filter(Boolean);

      const res = await apiFetch('/api/tickets', {
        method: 'POST',
        body: JSON.stringify({
          subject: form.subject,
          description: form.description,
          priority: form.priority,
          tags: tags.length ? tags : undefined,
        }),
      });
      const ticket = await res.json();
      navigate(`/tickets/${ticket.id}`, { replace: true });
    } catch (err) {
      if (err.status === 422) {
        // Re-fetch to get validation errors from the response
        try {
          const res = await apiFetch('/api/tickets', {
            method: 'POST',
            body: JSON.stringify({
              subject: form.subject,
              description: form.description,
              priority: form.priority,
            }),
          });
        } catch (e2) {
          // The first error already has the message; try to parse field errors
        }
        setErrors({ general: err.message });
      } else {
        setErrors({ general: err.message || 'Failed to create ticket' });
      }
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="p-6 max-w-2xl">
      <h1 className="text-2xl font-bold text-gray-900 mb-6">New Ticket</h1>

      {errors.general && (
        <div className="mb-4 rounded-md bg-red-50 p-3 text-sm text-red-700">
          {errors.general}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-5">
        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Subject
          </label>
          <input
            type="text"
            required
            value={form.subject}
            onChange={update('subject')}
            className="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            placeholder="Brief summary of the issue"
          />
          {errors.subject && (
            <p className="mt-1 text-sm text-red-600">{errors.subject}</p>
          )}
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Description
          </label>
          <textarea
            required
            rows={6}
            value={form.description}
            onChange={update('description')}
            className="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            placeholder="Describe the issue in detail…"
          />
          {errors.description && (
            <p className="mt-1 text-sm text-red-600">{errors.description}</p>
          )}
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Priority
          </label>
          <select
            value={form.priority}
            onChange={update('priority')}
            className="rounded-md border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
          >
            {PRIORITIES.map((p) => (
              <option key={p} value={p}>
                {p.charAt(0).toUpperCase() + p.slice(1)}
              </option>
            ))}
          </select>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">
            Tags <span className="text-gray-400 font-normal">(comma-separated)</span>
          </label>
          <input
            type="text"
            value={form.tags}
            onChange={update('tags')}
            className="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            placeholder="bug, urgent, frontend"
          />
        </div>

        <div className="flex gap-3 pt-2">
          <button
            type="submit"
            disabled={submitting}
            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
          >
            {submitting ? 'Creating…' : 'Create Ticket'}
          </button>
          <button
            type="button"
            onClick={() => navigate('/tickets')}
            className="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            Cancel
          </button>
        </div>
      </form>
    </div>
  );
}
