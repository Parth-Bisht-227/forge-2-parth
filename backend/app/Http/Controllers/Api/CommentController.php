<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Ticket;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request, Ticket $ticket)
    {
        $this->authorizeTenant($request, $ticket);

        return response()->json($ticket->comments()->with('user')->latest()->get());
    }

    public function store(Request $request, Ticket $ticket)
    {
        $this->authorizeTenant($request, $ticket);

        $data = $request->validate([
            'body' => ['required', 'string'],
        ]);

        $comment = Comment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return response()->json($comment->load('user'), 201);
    }

    public function destroy(Request $request, Ticket $ticket, Comment $comment)
    {
        $this->authorizeTenant($request, $ticket);

        if ($comment->ticket_id !== $ticket->id) {
            abort(404);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted']);
    }

    private function authorizeTenant(Request $request, Ticket $ticket): void
    {
        if ($ticket->organization_id !== $request->user()->organization_id) {
            abort(403, 'This ticket does not belong to your organization.');
        }
    }
}
