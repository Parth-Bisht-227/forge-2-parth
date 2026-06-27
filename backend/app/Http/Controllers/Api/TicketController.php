<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Ticket::where('organization_id', $user->organization_id);

        // Visibility: customers see only their own tickets
        if ($user->role === 'customer') {
            $query->where('requester_id', $user->id);
        }

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->input('priority'));
        }
        if ($request->filled('assignee_id')) {
            $query->where('assignee_id', $request->input('assignee_id'));
        }
        if ($request->filled('q')) {
            $search = $request->input('q');
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $tickets = $query->with(['requester', 'assignee'])
            ->latest()
            ->paginate(15);

        return response()->json($tickets);
    }

    public function store(StoreTicketRequest $request)
    {
        $user = $request->user();

        $ticket = Ticket::create([
            'organization_id' => $user->organization_id,
            'requester_id' => $user->id,
            'subject' => $request->input('subject'),
            'description' => $request->input('description'),
            'status' => 'open',
            'priority' => $request->input('priority', 'medium'),
            'assignee_id' => $request->input('assignee_id'),
            'tags' => $request->input('tags'),
        ]);

        return response()->json($ticket->load(['requester', 'assignee']), 201);
    }

    public function show(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        // Tenant rule: 404 if not in user's org (don't leak existence)
        if ($ticket->organization_id !== $user->organization_id) {
            abort(404);
        }

        // Visibility: customers see only their own tickets
        if ($user->role === 'customer' && $ticket->requester_id !== $user->id) {
            abort(404);
        }

        // Load comments, but filter out internal for customers
        $ticket->load(['requester', 'assignee']);
        if ($user->role === 'customer') {
            $ticket->setRelation('comments', $ticket->comments()->where('type', 'public')->with('user')->get());
        } else {
            $ticket->load('comments.user');
        }

        return response()->json($ticket);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
        $user = $request->user();

        if ($ticket->organization_id !== $user->organization_id) {
            abort(404);
        }

        if ($user->role === 'customer' && $ticket->requester_id !== $user->id) {
            abort(404);
        }

        $ticket->update($request->validated());

        return response()->json($ticket->fresh(['requester', 'assignee', 'comments.user']));
    }
}
