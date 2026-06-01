<?php

namespace App\Http\Controllers\Api;

use App\Models\Belt;
use App\Models\Event;
use App\Models\Fight;
use App\Models\Fighter;
use App\Models\Promoter;
use App\Models\Ranking;
use App\Models\Venue;

class BoxingHomeController extends BoxingApiController
{
    public function __invoke()
    {
        $eventRelations = [
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

        $featuredEvent = Event::query()
            ->with($eventRelations)
            ->where('slug', 'usyk-vs-fury-2')
            ->first()
            ?? Event::query()
                ->with($eventRelations)
                ->upcoming()
                ->first();

        $upcomingEvents = Event::query()
            ->with($eventRelations)
            ->upcoming()
            ->limit(4)
            ->get();

        $latestResults = Fight::query()
            ->with([
                'event.venue.country',
                'event.promoter',
                'redCorner.country',
                'redCorner.stance',
                'redCorner.weightClass',
                'blueCorner.country',
                'blueCorner.stance',
                'blueCorner.weightClass',
                'winner.country',
                'winner.stance',
                'winner.weightClass',
                'weightClass',
                'resultMethod',
            ])
            ->where('status', 'completed')
            ->orderByDesc('fight_date')
            ->limit(4)
            ->get();

        $rankings = Ranking::query()
            ->with(['organisation', 'weightClass', 'fighter.country', 'fighter.stance', 'fighter.weightClass'])
            ->whereHas('organisation', fn ($query) => $query->where('abbreviation', 'WBA'))
            ->whereHas('weightClass', fn ($query) => $query->where('slug', 'heavyweight'))
            ->orderBy('rank')
            ->limit(5)
            ->get();

        $broadcasts = $upcomingEvents
            ->flatMap(fn (Event $event) => $event->broadcasts->map(fn ($broadcast) => [
                'event' => $this->eventSummary($event),
                'broadcast' => [
                    'region' => $broadcast->region,
                    'platform' => $broadcast->platform,
                    'is_ppv' => $broadcast->is_ppv,
                    'broadcaster' => $broadcast->broadcaster->name,
                ],
            ]))
            ->take(4)
            ->values();

        return response()->json([
            'featured_event' => $featuredEvent ? $this->eventDetail($featuredEvent) : null,
            'upcoming_events' => $upcomingEvents->map(fn (Event $event) => $this->eventSummary($event))->values(),
            'latest_results' => $latestResults->map(fn (Fight $fight) => $this->fightSummary($fight))->values(),
            'rankings' => $rankings->map(fn (Ranking $ranking) => $this->rankingSummary($ranking))->values(),
            'broadcasts' => $broadcasts,
            'news' => [
                ['title' => 'Usyk vs Fury 2 official for December 2026', 'timestamp' => '2 hours ago'],
                ['title' => 'Beterbiev and Bivol rematch ordered', 'timestamp' => '5 hours ago'],
                ['title' => 'Parker wants the Dubois winner next', 'timestamp' => '1 day ago'],
            ],
            'stats' => [
                'fighters' => Fighter::count(),
                'events' => Event::count(),
                'fights' => Fight::count(),
                'promoters' => Promoter::count(),
                'titles' => Belt::count(),
                'venues' => Venue::count(),
            ],
        ]);
    }
}
