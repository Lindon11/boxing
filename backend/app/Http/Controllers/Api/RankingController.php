<?php

namespace App\Http\Controllers\Api;

use App\Models\Organisation;
use App\Models\Ranking;
use App\Models\WeightClass;
use Illuminate\Http\Request;

class RankingController extends BoxingApiController
{
    public function index(Request $request)
    {
        $organisationSlug = $request->string('organisation', 'wba')->toString();
        $weightClassSlug = $request->string('weight_class', 'heavyweight')->toString();

        $rankings = Ranking::query()
            ->with(['organisation', 'weightClass', 'fighter.country', 'fighter.stance', 'fighter.weightClass'])
            ->whereHas('organisation', fn ($query) => $query->where('slug', $organisationSlug)->orWhere('abbreviation', strtoupper($organisationSlug)))
            ->whereHas('weightClass', fn ($query) => $query->where('slug', $weightClassSlug))
            ->orderBy('rank')
            ->get();

        return response()->json([
            'rankings' => $rankings->map(fn (Ranking $ranking) => $this->rankingSummary($ranking))->values(),
            'filters' => [
                'organisations' => Organisation::query()->orderBy('name')->get(['name', 'abbreviation', 'slug']),
                'weight_classes' => WeightClass::query()->orderBy('sort_order')->get(['name', 'slug']),
            ],
            'selected' => [
                'organisation' => $organisationSlug,
                'weight_class' => $weightClassSlug,
            ],
        ]);
    }
}
