<?php

namespace App\Http\Controllers\Api;

use App\Models\Belt;
use App\Models\Event;
use App\Models\Fighter;
use App\Models\Promoter;
use App\Models\Venue;
use Illuminate\Http\Request;

class SearchController extends BoxingApiController
{
    public function __invoke(Request $request)
    {
        $query = trim($request->string('q')->toString());
        $term = '%'.$query.'%';

        if ($query === '') {
            return response()->json([
                'query' => $query,
                'fighters' => [],
                'events' => [],
                'promoters' => [],
                'venues' => [],
                'titles' => [],
            ]);
        }

        $fighters = Fighter::query()
            ->with(['country', 'stance', 'weightClass'])
            ->where('display_name', 'like', $term)
            ->orWhere('ring_name', 'like', $term)
            ->orWhereHas('aliases', fn ($query) => $query->where('alias', 'like', $term))
            ->orderBy('display_name')
            ->limit(8)
            ->get();

        $events = Event::query()
            ->with([
                'venue.country',
                'promoter',
                'fights.redCorner.country',
                'fights.redCorner.stance',
                'fights.redCorner.weightClass',
                'fights.blueCorner.country',
                'fights.blueCorner.stance',
                'fights.blueCorner.weightClass',
                'fights.weightClass',
            ])
            ->where('name', 'like', $term)
            ->orWhere('subtitle', 'like', $term)
            ->orderByDesc('event_date')
            ->limit(8)
            ->get();

        $promoters = Promoter::query()
            ->where('name', 'like', $term)
            ->orderBy('name')
            ->limit(5)
            ->get(['name', 'slug', 'website_url']);

        $venues = Venue::query()
            ->with('country')
            ->where('name', 'like', $term)
            ->orWhere('city', 'like', $term)
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(fn (Venue $venue) => [
                'name' => $venue->name,
                'slug' => $venue->slug,
                'city' => $venue->city,
                'country' => $venue->country?->name,
            ]);

        $titles = Belt::query()
            ->with(['organisation', 'weightClass', 'currentReign.fighter.country', 'currentReign.fighter.stance', 'currentReign.fighter.weightClass'])
            ->where('name', 'like', $term)
            ->orWhereHas('organisation', fn ($query) => $query->where('name', 'like', $term)->orWhere('abbreviation', 'like', $term))
            ->orWhereHas('weightClass', fn ($query) => $query->where('name', 'like', $term))
            ->limit(8)
            ->get();

        return response()->json([
            'query' => $query,
            'fighters' => $fighters->map(fn (Fighter $fighter) => $this->fighterSummary($fighter))->values(),
            'events' => $events->map(fn (Event $event) => $this->eventSummary($event))->values(),
            'promoters' => $promoters,
            'venues' => $venues,
            'titles' => $titles->map(fn (Belt $belt) => $this->titleSummary($belt))->values(),
        ]);
    }
}
