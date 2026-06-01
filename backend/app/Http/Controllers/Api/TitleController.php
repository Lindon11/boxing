<?php

namespace App\Http\Controllers\Api;

use App\Models\Belt;

class TitleController extends BoxingApiController
{
    public function index()
    {
        $belts = Belt::query()
            ->with([
                'organisation',
                'weightClass',
                'currentReign.fighter.country',
                'currentReign.fighter.stance',
                'currentReign.fighter.weightClass',
            ])
            ->where('active', true)
            ->get()
            ->sort(function (Belt $a, Belt $b) {
                return [$a->weightClass->sort_order, $a->organisation->abbreviation]
                    <=> [$b->weightClass->sort_order, $b->organisation->abbreviation];
            })
            ->values();

        return response()->json([
            'titles' => $belts->map(fn (Belt $belt) => $this->titleSummary($belt))->values(),
        ]);
    }
}
