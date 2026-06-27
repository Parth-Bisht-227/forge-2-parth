<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Models\Comment;
use App\Models\Ticket;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        if ($ticket->organization_id !== $user->organization_id) {
            abort(404);
        }

        if ($user->role === 'customer' && $ticket->requester_id !== $user->id) {
            abort(404);
        }

        $query = $ticket->comments()->with('user')->latest();

        // Customers never see internal comments
        if ($user->role === 'customer') {
            $query->where('type', 'public');
        }

        return response()->json($query->get());
    }

    public function store(StoreCommentRequest $request, Ticket $ticket)
    {
        $user = $request->user();

        if ($ticket->organization_id !== $user->organization_id) {
            abort(404);
        }

        if ($user->role === 'customer' && $ticket->requester_id !== $user->id) {
            abort(404);
        }

        $type = $request->input('type', 'public');

        // Only agents and admins can create internal notes
        if ($type === 'internal' && $user->role === 'customer') {
            abort(403, 'Customers cannot create internal notes.');
        }

        $comment = Comment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'body' => $request->input('body'),
            'type' => $type,
        ]);

        return response()->json($comment->load('user'), 201);
    }

    public function destroy(Request $request, Ticket $ticket, Comment $comment)
    {
        $user = $request->user();

        if ($ticket->organization_id !== $user->organization_id) {
            abort(404);
        }

        if ($comment->ticket_id !== $ticket->id) {
            abort(404);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted']);
    }
}
