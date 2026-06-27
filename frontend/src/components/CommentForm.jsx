import { useState } from 'react';
import { apiFetch } from '../lib/api';
import { useAuth } from '../context/AuthContext';

export default function CommentForm({ ticketId, onCommentCreated }) {
  const { user } = useAuth();
  const [body, setBody] = useState('');
  const [type, setType] = useState('public');
  const [error, setError] = useState('');
  const [submitting, setSubmitting] = useState(false);

  const isStaff = user?.role === 'agent' || user?.role === 'admin';

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!body.trim()) return;
    setError('');
    setSubmitting(true);
    try {
      const res = await apiFetch(`/api/tickets/${ticketId}/comments`, {
        method: 'POST',
        body: JSON.stringify({ body, type }),
      });
      const comment = await res.json();
      onCommentCreated(comment);
      setBody('');
      setType('public');
    } catch (err) {
      setError(err.message || 'Failed to post comment');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-3">
      {error && (
        <div className="rounded-md bg-red-50 p-3 text-sm text-red-700">{error}</div>
      )}

      <textarea
        rows={3}
        value={body}
        onChange={(e) => setBody(e.target.value)}
        placeholder="Write a reply…"
        className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
      />

      {isStaff && (
        <div className="flex items-center gap-4">
          <label className="flex items-center gap-1.5 text-sm text-gray-700">
            <input
              type="radio"
              name="comment-type"
              value="public"
              checked={type === 'public'}
              onChange={(e) => setType(e.target.value)}
              className="text-indigo-600"
            />
            Public reply
          </label>
          <label className="flex items-center gap-1.5 text-sm text-gray-700">
            <input
              type="radio"
              name="comment-type"
              value="internal"
              checked={type === 'internal'}
              onChange={(e) => setType(e.target.value)}
              className="text-indigo-600"
            />
            Internal note
          </label>
        </div>
      )}

      <div className="flex justify-end">
        <button
          type="submit"
          disabled={submitting || !body.trim()}
          className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
        >
          {submitting ? 'Posting…' : type === 'internal' ? 'Add Internal Note' : 'Post Reply'}
        </button>
      </div>
    </form>
  );
}
