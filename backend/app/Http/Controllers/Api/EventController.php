<?php

namespace App\Http\Controllers\Api;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends BoxingApiController
{
    private array $relations = [
        'venue.country',
        'promoter',
        'fights.redCorner.country',
        'fights.redCorner.stance',
        'fights.redCorner.weightClass',
        'fights.blueCorner.country',
        'fights.blueCorner.stance',
        'fights.blueCorner.weightClass',
        'fights.winner.country',
        'fights.winner.stance',
        'fights.winner.weightClass',
        'fights.weightClass',
        'fights.resultMethod',
        'broadcasts.broadcaster',
    ];

    public function index(Request $request)
    {
        $events = Event::query()
            ->with($this->relations)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('promoter'), fn ($query) => $query->whereHas('promoter', fn ($query) => $query->where('slug', $request->string('promoter'))))
            ->when($request->filled('venue'), fn ($query) => $query->whereHas('venue', fn ($query) => $query->where('slug', $request->string('venue'))))
            ->when($request->filled('weight_class'), fn ($query) => $query->whereHas('fights.weightClass', fn ($query) => $query->where('slug', $request->string('weight_class'))))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('event_date', '>=', $request->string('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('event_date', '<=', $request->string('date_to')))
            ->orderByRaw("case when status = 'upcoming' then 0 else 1 end")
            ->orderBy('event_date')
            ->paginate(18);

        return response()->json([
            'events' => $events->through(fn (Event $event) => $this->eventSummary($event)),
            'filters' => $this->filters(),
        ]);
    }

    public function show(Event $event)
    {
        $event->load($this->relations);

        return response()->json([
            'event' => $this->eventDetail($event),
        ]);
    }

    public function fightCard(Event $event)
    {
        $event->load($this->relations);

        return response()->json([
            'event' => $this->eventSummary($event),
            'fights' => $event->fights->map(fn ($fight) => $this->fightSummary($fight, false))->values(),
        ]);
    }
}
