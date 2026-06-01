<?php

namespace App\Http\Controllers\Api;

use App\Models\Country;
use App\Models\Fight;
use App\Models\Fighter;
use App\Models\Stance;
use App\Models\WeightClass;
use Illuminate\Http\Request;

class FighterController extends BoxingApiController
{
    public function index(Request $request)
    {
        $fighters = Fighter::query()
            ->with(['country', 'stance', 'weightClass'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(function ($query) use ($term) {
                    $query
                        ->where('display_name', 'like', $term)
                        ->orWhere('ring_name', 'like', $term)
                        ->orWhereHas('aliases', fn ($query) => $query->where('alias', 'like', $term));
                });
            })
            ->when($request->filled('country'), fn ($query) => $query->whereHas('country', fn ($query) => $query->where('code', $request->string('country')->upper())))
            ->when($request->filled('weight_class'), fn ($query) => $query->whereHas('weightClass', fn ($query) => $query->where('slug', $request->string('weight_class'))))
            ->when($request->filled('stance'), fn ($query) => $query->whereHas('stance', fn ($query) => $query->where('slug', $request->string('stance'))))
            ->orderBy('display_name')
            ->paginate(24);

        return response()->json([
            'fighters' => $fighters->through(fn (Fighter $fighter) => $this->fighterSummary($fighter)),
            'filters' => [
                'countries' => Country::query()->orderBy('name')->get(['name', 'code']),
                'stances' => Stance::query()->orderBy('name')->get(['name', 'slug']),
                'weight_classes' => WeightClass::query()->orderBy('sort_order')->get(['name', 'slug']),
            ],
        ]);
    }

    public function show(Fighter $fighter)
    {
        $fighter->load([
            'country',
            'stance',
            'weightClass',
            'aliases',
            'currentBelts.belt.organisation',
            'currentBelts.belt.weightClass',
            'rankings.organisation',
            'rankings.weightClass',
        ]);

        $fights = Fight::query()
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
            ->where(function ($query) use ($fighter) {
                $query
                    ->where('red_corner_fighter_id', $fighter->id)
                    ->orWhere('blue_corner_fighter_id', $fighter->id);
            })
            ->orderByDesc('fight_date')
            ->get();

        $upcomingFight = $fights->first(fn (Fight $fight) => $fight->status !== 'completed');
        $lastFight = $fights->first(fn (Fight $fight) => $fight->status === 'completed');

        return response()->json([
            'fighter' => $this->fighterDetail($fighter, [
                'titles' => $fighter->currentBelts->map(fn ($history) => $this->beltHistorySummary($history))->values(),
                'rankings' => $fighter->rankings->sortBy('rank')->map(fn ($ranking) => $this->rankingSummary($ranking))->values(),
                'fight_history' => $fights->map(fn (Fight $fight) => $this->fightSummary($fight))->values(),
                'upcoming_fight' => $upcomingFight ? $this->fightSummary($upcomingFight) : null,
                'last_fight' => $lastFight ? $this->fightSummary($lastFight) : null,
            ]),
        ]);
    }
}
