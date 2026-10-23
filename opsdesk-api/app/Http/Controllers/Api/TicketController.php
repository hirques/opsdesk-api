<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', Ticket::class);
        $query = Ticket::query()->with(['creator:id,name', 'assignee:id,name'])->latest();

        if (! $request->user()->isStaff()) {
            $query->where('created_by', $request->user()->id);
        }

        return TicketResource::collection($query->paginate(20));
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        Gate::authorize('create', Ticket::class);
        $input = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:10000'],
            'priority' => ['sometimes', Rule::in(['low', 'normal', 'high'])],
        ]);

        $ticket = Ticket::create([
            'title' => $input['title'],
            'description' => $input['description'] ?? null,
            'priority' => $input['priority'] ?? 'normal',
            'status' => 'open',
            'created_by' => $request->user()->id,
        ]);

        return (new TicketResource($ticket->load(['creator:id,name', 'assignee:id,name'])))
            ->response()->setStatusCode(201);
    }

    public function show(Ticket $ticket): TicketResource
    {
        Gate::authorize('view', $ticket);
        return new TicketResource($ticket->load(['creator:id,name', 'assignee:id,name']));
    }

    public function update(Request $request, Ticket $ticket): TicketResource
    {
        Gate::authorize('update', $ticket);
        abort_if($request->exists('assigned_to') && $request->user()->role !== 'admin', 403);

        $input = $request->validate([
            'title' => ['sometimes', 'string', 'max:160'],
            'description' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'priority' => ['sometimes', Rule::in(['low', 'normal', 'high'])],
            'status' => ['sometimes', Rule::in(['open', 'in_progress', 'resolved', 'closed'])],
            'assigned_to' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ]);

        $ticket->update($input);
        return new TicketResource($ticket->load(['creator:id,name', 'assignee:id,name']));
    }

    public function destroy(Ticket $ticket): \Illuminate\Http\Response
    {
        Gate::authorize('delete', $ticket);
        $ticket->delete();
        return response()->noContent();
    }
}
