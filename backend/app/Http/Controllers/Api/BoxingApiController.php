<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Belt;
use App\Models\BeltHistory;
use App\Models\Event;
use App\Models\EventBroadcast;
use App\Models\Fight;
use App\Models\Fighter;
use App\Models\Promoter;
use App\Models\Ranking;
use App\Models\Venue;
use App\Models\WeightClass;

abstract class BoxingApiController extends Controller
{
    protected function fighterSummary(?Fighter $fighter): ?array
    {
        if (!$fighter) {
            return null;
        }

        $country = $fighter->relationLoaded('country') ? $fighter->country : null;
        $stance = $fighter->relationLoaded('stance') ? $fighter->stance : null;
        $weightClass = $fighter->relationLoaded('weightClass') ? $fighter->weightClass : null;

        return [
            'id' => $fighter->id,
            'slug' => $fighter->slug,
            'display_name' => $fighter->display_name,
            'ring_name' => $fighter->ring_name,
            'record' => $fighter->record,
            'wins' => $fighter->wins,
            'losses' => $fighter->losses,
            'draws' => $fighter->draws,
            'no_contests' => $fighter->no_contests,
            'knockouts' => $fighter->knockouts,
            'country' => $country ? [
                'name' => $country->name,
                'code' => $country->code,
            ] : null,
            'stance' => $stance?->name,
            'weight_class' => $weightClass ? [
                'name' => $weightClass->name,
                'slug' => $weightClass->slug,
            ] : null,
            'photo_url' => $fighter->photo_url,
        ];
    }

    protected function fighterDetail(Fighter $fighter, array $extra = []): array
    {
        return array_merge($this->fighterSummary($fighter), [
            'first_name' => $fighter->first_name,
            'last_name' => $fighter->last_name,
            'birth_date' => $fighter->birth_date?->toDateString(),
            'birth_place' => $fighter->birth_place,
            'residence' => $fighter->residence,
            'height_cm' => $fighter->height_cm,
            'reach_cm' => $fighter->reach_cm,
            'debut_date' => $fighter->debut_date?->toDateString(),
            'active' => $fighter->active,
            'bio' => $fighter->bio,
            'aliases' => $fighter->relationLoaded('aliases') ? $fighter->aliases->pluck('alias')->values() : [],
        ], $extra);
    }

    protected function eventSummary(?Event $event): ?array
    {
        if (!$event) {
            return null;
        }

        $mainFight = $event->relationLoaded('fights')
            ? $event->fights->firstWhere('billing', 'main_event')
            : null;
        $venue = $event->relationLoaded('venue') ? $event->venue : null;
        $promoter = $event->relationLoaded('promoter') ? $event->promoter : null;

        return [
            'id' => $event->id,
            'slug' => $event->slug,
            'name' => $event->name,
            'subtitle' => $event->subtitle,
            'status' => $event->status,
            'event_date' => $event->event_date?->toISOString(),
            'ring_walks_at' => $event->ring_walks_at?->toISOString(),
            'poster_url' => $event->poster_url,
            'hero_image_url' => $event->hero_image_url,
            'broadcast_notes' => $event->broadcast_notes,
            'ticket_url' => $event->ticket_url,
            'venue' => $venue ? [
                'name' => $venue->name,
                'slug' => $venue->slug,
                'city' => $venue->city,
                'region' => $venue->region,
                'country' => $venue->relationLoaded('country') ? $venue->country?->name : null,
                'country_code' => $venue->relationLoaded('country') ? $venue->country?->code : null,
                'capacity' => $venue->capacity,
            ] : null,
            'promoter' => $promoter ? [
                'name' => $promoter->name,
                'slug' => $promoter->slug,
            ] : null,
            'main_fight' => $mainFight ? $this->fightSummary($mainFight, false) : null,
        ];
    }

    protected function eventDetail(Event $event): array
    {
        return array_merge($this->eventSummary($event), [
            'fights' => $event->fights->map(fn (Fight $fight) => $this->fightSummary($fight, false))->values(),
            'broadcasts' => $event->broadcasts->map(fn (EventBroadcast $broadcast) => [
                'region' => $broadcast->region,
                'platform' => $broadcast->platform,
                'is_ppv' => $broadcast->is_ppv,
                'details' => $broadcast->details,
                'broadcaster' => [
                    'name' => $broadcast->broadcaster->name,
                    'slug' => $broadcast->broadcaster->slug,
                    'website_url' => $broadcast->broadcaster->website_url,
                ],
            ])->values(),
        ]);
    }

    protected function fightSummary(Fight $fight, bool $includeEvent = true): array
    {
        $weightClass = $fight->relationLoaded('weightClass') ? $fight->weightClass : null;
        $redCorner = $fight->relationLoaded('redCorner') ? $fight->redCorner : null;
        $blueCorner = $fight->relationLoaded('blueCorner') ? $fight->blueCorner : null;
        $winner = $fight->relationLoaded('winner') ? $fight->winner : null;
        $resultMethod = $fight->relationLoaded('resultMethod') ? $fight->resultMethod : null;

        $data = [
            'id' => $fight->id,
            'title' => $fight->title,
            'billing' => $fight->billing,
            'bout_order' => $fight->bout_order,
            'scheduled_rounds' => $fight->scheduled_rounds,
            'completed_rounds' => $fight->completed_rounds,
            'status' => $fight->status,
            'fight_date' => $fight->fight_date?->toISOString(),
            'is_title_fight' => $fight->is_title_fight,
            'result_notes' => $fight->result_notes,
            'result_method' => $resultMethod ? [
                'name' => $resultMethod->name,
                'abbreviation' => $resultMethod->abbreviation,
            ] : null,
            'weight_class' => $weightClass ? [
                'name' => $weightClass->name,
                'slug' => $weightClass->slug,
            ] : null,
            'red_corner' => $this->fighterSummary($redCorner),
            'blue_corner' => $this->fighterSummary($blueCorner),
            'winner' => $this->fighterSummary($winner),
        ];

        if ($includeEvent && $fight->relationLoaded('event') && $fight->event) {
            $data['event'] = $this->eventSummary($fight->event);
        }

        return $data;
    }

    protected function rankingSummary(Ranking $ranking): array
    {
        return [
            'rank' => $ranking->rank,
            'points' => $ranking->points,
            'ranked_on' => $ranking->ranked_on?->toDateString(),
            'fighter' => $this->fighterSummary($ranking->fighter),
            'organisation' => [
                'name' => $ranking->organisation->name,
                'abbreviation' => $ranking->organisation->abbreviation,
                'slug' => $ranking->organisation->slug,
            ],
            'weight_class' => [
                'name' => $ranking->weightClass->name,
                'slug' => $ranking->weightClass->slug,
            ],
        ];
    }

    protected function titleSummary(Belt $belt): array
    {
        $current = $belt->relationLoaded('currentReign')
            ? $belt->currentReign->first()
            : null;

        return [
            'id' => $belt->id,
            'name' => $belt->name,
            'slug' => $belt->slug,
            'organisation' => [
                'name' => $belt->organisation->name,
                'abbreviation' => $belt->organisation->abbreviation,
                'slug' => $belt->organisation->slug,
            ],
            'weight_class' => [
                'name' => $belt->weightClass->name,
                'slug' => $belt->weightClass->slug,
            ],
            'champion' => $current ? $this->fighterSummary($current->fighter) : null,
            'reign_started_on' => $current?->reign_started_on?->toDateString(),
        ];
    }

    protected function filters(): array
    {
        return [
            'weight_classes' => WeightClass::query()->orderBy('sort_order')->get(['name', 'slug']),
            'promoters' => Promoter::query()->orderBy('name')->get(['name', 'slug']),
            'venues' => Venue::query()->orderBy('name')->get(['name', 'slug', 'city']),
        ];
    }

    protected function beltHistorySummary(BeltHistory $history): array
    {
        return [
            'status' => $history->status,
            'reign_started_on' => $history->reign_started_on?->toDateString(),
            'reign_ended_on' => $history->reign_ended_on?->toDateString(),
            'result' => $history->result,
            'belt' => [
                'name' => $history->belt->name,
                'organisation' => $history->belt->organisation->abbreviation,
                'weight_class' => $history->belt->weightClass->name,
            ],
        ];
    }
}
