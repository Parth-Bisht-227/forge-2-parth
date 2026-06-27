<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $tickets = Ticket::where('organization_id', $request->user()->organization_id)
            ->with(['creator', 'comments'])
            ->latest()
            ->get();

        return response()->json($tickets);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['sometimes', 'string', Rule::in(['low', 'normal', 'high', 'urgent'])],
        ]);

        $ticket = Ticket::create([
            'organization_id' => $request->user()->organization_id,
            'created_by' => $request->user()->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => 'open',
            'priority' => $data['priority'] ?? 'normal',
        ]);

        return response()->json($ticket->load('creator'), 201);
    }

    public function show(Request $request, Ticket $ticket)
    {
        $this->authorizeTenant($request, $ticket);

        return response()->json($ticket->load(['creator', 'comments.user']));
    }

    public function update(Request $request, Ticket $ticket)
    {
        $this->authorizeTenant($request, $ticket);

        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'status' => ['sometimes', 'string', Rule::in(['open', 'in_progress', 'resolved', 'closed'])],
            'priority' => ['sometimes', 'string', Rule::in(['low', 'normal', 'high', 'urgent'])],
        ]);

        $ticket->update($data);

        return response()->json($ticket->fresh(['creator', 'comments.user']));
    }

    public function destroy(Request $request, Ticket $ticket)
    {
        $this->authorizeTenant($request, $ticket);

        $ticket->delete();

        return response()->json(['message' => 'Ticket deleted'], 200);
    }

    private function authorizeTenant(Request $request, Ticket $ticket): void
    {
        if ($ticket->organization_id !== $request->user()->organization_id) {
            abort(403, 'This ticket does not belong to your organization.');
        }
    }
}
