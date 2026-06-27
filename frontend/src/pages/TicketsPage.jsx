import { useState, useEffect, useRef, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { apiFetch } from '../lib/api';
import { useAuth } from '../context/AuthContext';
import { StatusBadge, PriorityBadge } from '../components/Badges';

const STATUS_OPTIONS = ['', 'open', 'in_progress', 'resolved', 'closed'];
const PRIORITY_OPTIONS = ['', 'low', 'medium', 'high', 'urgent'];

function formatDate(iso) {
  if (!iso) return '';
  return new Date(iso).toLocaleDateString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

export default function TicketsPage() {
  const { user } = useAuth();

  // Filters
  const [status, setStatus] = useState('');
  const [priority, setPriority] = useState('');
  const [assigneeId, setAssigneeId] = useState('');
  const [search, setSearch] = useState('');

  // Debounced search
  const [debouncedSearch, setDebouncedSearch] = useState('');
  const debounceRef = useRef(null);

  // Data
  const [tickets, setTickets] = useState([]);
  const [assignees, setAssignees] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Debounce search input by 400ms
  useEffect(() => {
    if (debounceRef.current) clearTimeout(debounceRef.current);
    debounceRef.current = setTimeout(() => {
      setDebouncedSearch(search);
    }, 400);
    return () => {
      if (debounceRef.current) clearTimeout(debounceRef.current);
    };
  }, [search]);

  // Fetch assignees once on mount from unfiltered ticket list
  useEffect(() => {
    let cancelled = false;
    apiFetch('/api/tickets?page=1')
      .then((r) => r.json())
      .then((data) => {
        if (cancelled) return;
        const seen = new Map();
        for (const t of data.data || []) {
          if (t.assignee && !seen.has(t.assignee.id)) {
            seen.set(t.assignee.id, t.assignee);
          }
        }
        setAssignees([...seen.values()]);
      })
      .catch(() => {
        // best-effort — dropdown stays empty
      });
    return () => {
      cancelled = true;
    };
  }, []);

  // Fetch tickets when filters change
  const fetchTickets = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const params = new URLSearchParams();
      if (status) params.set('status', status);
      if (priority) params.set('priority', priority);
      if (assigneeId) params.set('assignee_id', assigneeId);
      if (debouncedSearch) params.set('q', debouncedSearch);

      const res = await apiFetch(`/api/tickets?${params.toString()}`);
      const data = await res.json();
      setTickets(data.data || []);
    } catch (err) {
      setError(err.message || 'Failed to load tickets');
    } finally {
      setLoading(false);
    }
  }, [status, priority, assigneeId, debouncedSearch]);

  useEffect(() => {
    fetchTickets();
  }, [fetchTickets]);

  return (
    <div className="p-6">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900">Tickets</h1>
        <Link
          to="/tickets/new"
          className="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
        >
          + New Ticket
        </Link>
      </div>

      {/* Filters */}
      <div className="mb-4 flex flex-wrap gap-3">
        <select
          value={status}
          onChange={(e) => setStatus(e.target.value)}
          className="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        >
          {STATUS_OPTIONS.map((s) => (
            <option key={s} value={s}>
              {s ? s.replace('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase()) : 'All Statuses'}
            </option>
          ))}
        </select>

        <select
          value={priority}
          onChange={(e) => setPriority(e.target.value)}
          className="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        >
          {PRIORITY_OPTIONS.map((p) => (
            <option key={p} value={p}>
              {p ? p.charAt(0).toUpperCase() + p.slice(1) : 'All Priorities'}
            </option>
          ))}
        </select>

        <select
          value={assigneeId}
          onChange={(e) => setAssigneeId(e.target.value)}
          className="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        >
          <option value="">All Assignees</option>
          {assignees.map((a) => (
            <option key={a.id} value={a.id}>
              {a.name}
            </option>
          ))}
        </select>

        <input
          type="text"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          placeholder="Search subject or description…"
          className="flex-1 min-w-[200px] rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
        />
      </div>

      {/* States */}
      {loading && (
        <div className="py-12 text-center text-gray-400">Loading tickets…</div>
      )}

      {!loading && error && (
        <div className="py-12 text-center">
          <p className="text-red-600 mb-3">{error}</p>
          <button
            onClick={fetchTickets}
            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
          >
            Retry
          </button>
        </div>
      )}

      {!loading && !error && tickets.length === 0 && (
        <div className="py-12 text-center text-gray-400">
          No tickets found. Try adjusting your filters.
        </div>
      )}

      {/* Ticket list */}
      {!loading && !error && tickets.length > 0 && (
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="border-b border-gray-200 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                <th className="py-3 pr-4">Subject</th>
                <th className="py-3 pr-4">Status</th>
                <th className="py-3 pr-4">Priority</th>
                <th className="py-3 pr-4">Requester</th>
                <th className="py-3 pr-4">Assignee</th>
                <th className="py-3 pr-4">Tags</th>
                <th className="py-3 pr-4">Updated</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {tickets.map((ticket) => (
                <tr key={ticket.id} className="hover:bg-gray-50">
                  <td className="py-3 pr-4">
                    <Link
                      to={`/tickets/${ticket.id}`}
                      className="text-sm font-medium text-indigo-600 hover:text-indigo-800"
                    >
                      {ticket.subject}
                    </Link>
                  </td>
                  <td className="py-3 pr-4">
                    <StatusBadge status={ticket.status} />
                  </td>
                  <td className="py-3 pr-4">
                    <PriorityBadge priority={ticket.priority} />
                  </td>
                  <td className="py-3 pr-4 text-sm text-gray-700">
                    {ticket.requester?.name || '—'}
                  </td>
                  <td className="py-3 pr-4 text-sm text-gray-700">
                    {ticket.assignee?.name || (
                      <span className="text-gray-400 italic">Unassigned</span>
                    )}
                  </td>
                  <td className="py-3 pr-4">
                    <div className="flex flex-wrap gap-1">
                      {(ticket.tags || []).map((tag, i) => (
                        <span
                          key={i}
                          className="inline-flex items-center rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600"
                        >
                          {tag}
                        </span>
                      ))}
                    </div>
                  </td>
                  <td className="py-3 pr-4 text-sm text-gray-500">
                    {formatDate(ticket.updated_at)}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
}
