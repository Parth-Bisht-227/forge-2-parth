import { useState, useEffect, useCallback } from 'react';
import { useParams, Link } from 'react-router-dom';
import { apiFetch } from '../lib/api';
import { useAuth } from '../context/AuthContext';
import { StatusBadge, PriorityBadge } from '../components/Badges';
import CommentForm from '../components/CommentForm';

const ROLE_BADGE_STYLES = {
  admin: 'bg-purple-100 text-purple-700',
  agent: 'bg-indigo-100 text-indigo-700',
  customer: 'bg-gray-100 text-gray-600',
};

function formatDate(iso) {
  if (!iso) return '';
  return new Date(iso).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

export default function TicketDetailPage() {
  const { id } = useParams();
  const { user } = useAuth();
  const [ticket, setTicket] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const isStaff = user?.role === 'agent' || user?.role === 'admin';

  const fetchTicket = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await apiFetch(`/api/tickets/${id}`);
      const data = await res.json();
      setTicket(data);
    } catch (err) {
      setError(err.message || 'Failed to load ticket');
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    fetchTicket();
  }, [fetchTicket]);

  const handleCommentCreated = (comment) => {
    setTicket((prev) => {
      if (!prev) return prev;
      const comments = [...(prev.comments || []), comment];
      // Sort oldest first for display
      comments.sort((a, b) => new Date(a.created_at) - new Date(b.created_at));
      return { ...prev, comments };
    });
  };

  if (loading) {
    return <div className="p-8 text-gray-400">Loading…</div>;
  }

  if (error) {
    return (
      <div className="p-8 text-center">
        <p className="text-red-600 mb-3">{error}</p>
        <button
          onClick={fetchTicket}
          className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
        >
          Retry
        </button>
      </div>
    );
  }

  if (!ticket) {
    return <div className="p-8 text-gray-400">Ticket not found.</div>;
  }

  // Sort comments oldest first (API returns latest first)
  const sortedComments = [...(ticket.comments || [])].sort(
    (a, b) => new Date(a.created_at) - new Date(b.created_at)
  );

  return (
    <div className="p-6 max-w-4xl">
      {/* Breadcrumb */}
      <div className="mb-4">
        <Link to="/tickets" className="text-sm text-indigo-600 hover:text-indigo-800">
          ← Back to Tickets
        </Link>
      </div>

      {/* Ticket Header */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div className="flex items-start justify-between gap-4 mb-4">
          <h1 className="text-xl font-bold text-gray-900">{ticket.subject}</h1>
          <div className="flex gap-2 flex-shrink-0">
            <StatusBadge status={ticket.status} />
            <PriorityBadge priority={ticket.priority} />
          </div>
        </div>

        <div className="grid grid-cols-2 gap-4 text-sm">
          <div>
            <span className="text-gray-500">Requester:</span>{' '}
            <span className="text-gray-900">{ticket.requester?.name || '—'}</span>
          </div>
          <div>
            <span className="text-gray-500">Assignee:</span>{' '}
            <span className="text-gray-900">
              {ticket.assignee?.name || <span className="text-gray-400 italic">Unassigned</span>}
            </span>
          </div>
          <div>
            <span className="text-gray-500">Created:</span>{' '}
            <span className="text-gray-900">{formatDate(ticket.created_at)}</span>
          </div>
          <div>
            <span className="text-gray-500">Updated:</span>{' '}
            <span className="text-gray-900">{formatDate(ticket.updated_at)}</span>
          </div>
        </div>

        {ticket.tags && ticket.tags.length > 0 && (
          <div className="mt-4 flex flex-wrap gap-1">
            {ticket.tags.map((tag, i) => (
              <span
                key={i}
                className="inline-flex items-center rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600"
              >
                {tag}
              </span>
            ))}
          </div>
        )}

        {/* Description */}
        <div className="mt-4 border-t border-gray-100 pt-4">
          <p className="text-sm text-gray-700 whitespace-pre-wrap">{ticket.description}</p>
        </div>
      </div>

      {/* Conversation */}
      <div className="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">
          Conversation ({sortedComments.length})
        </h2>

        {sortedComments.length === 0 && (
          <p className="text-gray-400 text-sm py-4">No comments yet.</p>
        )}

        <div className="space-y-4">
          {sortedComments.map((comment) => {
            const isInternal = comment.type === 'internal';
            const roleStyle = ROLE_BADGE_STYLES[comment.user?.role] || 'bg-gray-100 text-gray-600';

            return (
              <div
                key={comment.id}
                className={`rounded-lg p-4 ${
                  isInternal
                    ? 'bg-amber-50 border border-amber-200'
                    : 'bg-gray-50 border border-gray-200'
                }`}
              >
                <div className="flex items-center justify-between mb-2">
                  <div className="flex items-center gap-2">
                    <span className="text-sm font-medium text-gray-900">
                      {comment.user?.name || 'Unknown'}
                    </span>
                    <span
                      className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium capitalize ${roleStyle}`}
                    >
                      {comment.user?.role}
                    </span>
                    {isInternal && (
                      <span className="inline-flex items-center rounded-full bg-amber-200 px-2 py-0.5 text-xs font-medium text-amber-800">
                        Internal
                      </span>
                    )}
                  </div>
                  <span className="text-xs text-gray-400">
                    {formatDate(comment.created_at)}
                  </span>
                </div>
                <p className="text-sm text-gray-700 whitespace-pre-wrap">{comment.body}</p>
              </div>
            );
          })}
        </div>

        {/* Comment Form */}
        <div className="mt-6 border-t border-gray-100 pt-4">
          <CommentForm ticketId={ticket.id} onCommentCreated={handleCommentCreated} />
        </div>
      </div>
    </div>
  );
}
