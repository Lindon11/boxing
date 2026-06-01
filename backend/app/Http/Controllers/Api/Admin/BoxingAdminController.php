<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Belt;
use App\Models\BeltHistory;
use App\Models\Broadcaster;
use App\Models\Country;
use App\Models\Event;
use App\Models\EventBroadcast;
use App\Models\Fight;
use App\Models\Fighter;
use App\Models\Media;
use App\Models\Organisation;
use App\Models\Promoter;
use App\Models\Ranking;
use App\Models\ResultMethod;
use App\Models\Stance;
use App\Models\Venue;
use App\Models\WeightClass;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BoxingAdminController extends Controller
{
    private const MEDIA_TYPES = [
        'fighter' => Fighter::class,
        'event' => Event::class,
    ];

    public function resources()
    {
        return response()->json([
            'resources' => collect($this->resourceMap())->map(fn ($config, $key) => [
                'key' => $key,
                'label' => $config['label'],
                'description' => $config['description'] ?? null,
            ])->values(),
        ]);
    }

    public function options()
    {
        return response()->json([
            'countries' => Country::query()->orderBy('name')->get(['id', 'name', 'code']),
            'stances' => Stance::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'fighters' => Fighter::query()->orderBy('display_name')->get(['id', 'display_name', 'slug', 'wins', 'losses', 'draws', 'no_contests', 'knockouts']),
            'events' => Event::query()->orderByDesc('event_date')->get(['id', 'name', 'slug', 'event_date', 'status']),
            'fights' => Fight::query()
                ->with(['event:id,name', 'redCorner:id,display_name', 'blueCorner:id,display_name'])
                ->orderByDesc('fight_date')
                ->limit(300)
                ->get()
                ->map(fn (Fight $fight) => [
                    'id' => $fight->id,
                    'name' => trim(($fight->event?->name ? $fight->event->name.' - ' : '').$fight->redCorner?->display_name.' vs '.$fight->blueCorner?->display_name),
                ]),
            'weight_classes' => WeightClass::query()->orderBy('sort_order')->get(['id', 'name', 'slug']),
            'organisations' => Organisation::query()->orderBy('name')->get(['id', 'name', 'abbreviation', 'slug']),
            'promoters' => Promoter::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'venues' => Venue::query()->orderBy('name')->get(['id', 'name', 'city', 'slug']),
            'result_methods' => ResultMethod::query()->orderBy('name')->get(['id', 'name', 'abbreviation', 'slug']),
            'broadcasters' => Broadcaster::query()->orderBy('name')->get(['id', 'name', 'slug']),
            'belts' => Belt::query()
                ->with(['organisation:id,abbreviation', 'weightClass:id,name'])
                ->orderBy('name')
                ->get()
                ->map(fn (Belt $belt) => [
                    'id' => $belt->id,
                    'name' => $belt->name,
                    'label' => $belt->organisation?->abbreviation.' '.$belt->weightClass?->name,
                ]),
            'media_types' => [
                ['value' => 'fighter', 'label' => 'Fighter'],
                ['value' => 'event', 'label' => 'Event'],
            ],
            'event_statuses' => ['upcoming', 'completed', 'cancelled'],
            'fight_statuses' => ['scheduled', 'completed', 'cancelled'],
            'fight_billings' => ['main_event', 'co_main_event', 'undercard'],
        ]);
    }

    public function index(Request $request, string $resource)
    {
        $config = $this->resourceConfig($resource);
        $query = $this->baseQuery($resource, $config);
        $search = trim($request->string('q')->toString());

        if ($search !== '' && !empty($config['search'])) {
            $query->where(function (Builder $query) use ($config, $search) {
                foreach ($config['search'] as $column) {
                    $query->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }

        $items = $query
            ->orderBy($config['order'][0], $config['order'][1])
            ->paginate((int) $request->integer('per_page', 15));

        return response()->json([
            'items' => $items->through(fn (Model $model) => $this->serialize($resource, $model)),
        ]);
    }

    public function show(string $resource, int $id)
    {
        $config = $this->resourceConfig($resource);
        $model = $this->baseQuery($resource, $config)->findOrFail($id);

        return response()->json([
            'item' => $this->serialize($resource, $model),
        ]);
    }

    public function store(Request $request, string $resource)
    {
        $config = $this->resourceConfig($resource);
        $data = $this->validated($request, $resource);

        $model = $this->makeModel($resource, $data);
        $model->fill($this->payloadForModel($resource, $data));
        $model->save();

        $this->afterSave($resource, $model, $data);
        $model = $this->baseQuery($resource, $config)->findOrFail($model->id);

        return response()->json([
            'item' => $this->serialize($resource, $model),
            'message' => $config['label'].' created.',
        ], 201);
    }

    public function update(Request $request, string $resource, int $id)
    {
        $config = $this->resourceConfig($resource);
        $model = $config['model']::query()->findOrFail($id);
        $data = $this->validated($request, $resource, $model);

        $model->fill($this->payloadForModel($resource, $data));
        $model->save();

        $this->afterSave($resource, $model, $data);
        $model = $this->baseQuery($resource, $config)->findOrFail($model->id);

        return response()->json([
            'item' => $this->serialize($resource, $model),
            'message' => $config['label'].' updated.',
        ]);
    }

    public function destroy(string $resource, int $id)
    {
        $config = $this->resourceConfig($resource);
        $model = $config['model']::query()->findOrFail($id);
        $model->delete();

        return response()->json([
            'message' => $config['label'].' deleted.',
        ]);
    }

    private function resourceMap(): array
    {
        return [
            'fighters' => ['model' => Fighter::class, 'label' => 'Fighter', 'search' => ['display_name', 'ring_name', 'slug'], 'order' => ['display_name', 'asc'], 'relations' => ['country', 'stance', 'weightClass']],
            'events' => ['model' => Event::class, 'label' => 'Event', 'search' => ['name', 'subtitle', 'slug'], 'order' => ['event_date', 'desc'], 'relations' => ['venue.country', 'promoter', 'fights.redCorner', 'fights.blueCorner', 'fights.winner', 'fights.weightClass', 'fights.resultMethod', 'broadcasts.broadcaster']],
            'fights' => ['model' => Fight::class, 'label' => 'Fight', 'search' => ['title', 'billing', 'status'], 'order' => ['fight_date', 'desc'], 'relations' => ['event', 'redCorner', 'blueCorner', 'winner', 'weightClass', 'resultMethod']],
            'promoters' => ['model' => Promoter::class, 'label' => 'Promotion', 'search' => ['name', 'slug'], 'order' => ['name', 'asc'], 'relations' => ['country']],
            'venues' => ['model' => Venue::class, 'label' => 'Venue', 'search' => ['name', 'city', 'region', 'slug'], 'order' => ['name', 'asc'], 'relations' => ['country']],
            'weight-classes' => ['model' => WeightClass::class, 'label' => 'Weight Class', 'search' => ['name', 'slug'], 'order' => ['sort_order', 'asc'], 'relations' => []],
            'organisations' => ['model' => Organisation::class, 'label' => 'Organisation', 'search' => ['name', 'abbreviation', 'slug'], 'order' => ['name', 'asc'], 'relations' => []],
            'belts' => ['model' => Belt::class, 'label' => 'Belt', 'search' => ['name', 'slug'], 'order' => ['name', 'asc'], 'relations' => ['organisation', 'weightClass', 'currentReign.fighter']],
            'belt-history' => ['model' => BeltHistory::class, 'label' => 'Belt History', 'search' => ['status', 'result'], 'order' => ['reign_started_on', 'desc'], 'relations' => ['belt.organisation', 'belt.weightClass', 'fighter', 'event', 'fight']],
            'rankings' => ['model' => Ranking::class, 'label' => 'Ranking', 'search' => [], 'order' => ['rank', 'asc'], 'relations' => ['organisation', 'weightClass', 'fighter']],
            'broadcasters' => ['model' => Broadcaster::class, 'label' => 'Broadcaster', 'search' => ['name', 'slug'], 'order' => ['name', 'asc'], 'relations' => ['country']],
            'event-broadcasts' => ['model' => EventBroadcast::class, 'label' => 'Event Broadcast', 'search' => ['region', 'platform', 'details'], 'order' => ['region', 'asc'], 'relations' => ['event', 'broadcaster']],
            'media' => ['model' => Media::class, 'label' => 'Media', 'search' => ['title', 'collection', 'url'], 'order' => ['sort_order', 'asc'], 'relations' => ['mediable']],
        ];
    }

    private function resourceConfig(string $resource): array
    {
        abort_unless(isset($this->resourceMap()[$resource]), 404, 'Unknown BoxingDB resource.');

        return $this->resourceMap()[$resource];
    }

    private function baseQuery(string $resource, array $config): Builder
    {
        return $config['model']::query()->with($config['relations']);
    }

    private function validated(Request $request, string $resource, ?Model $model = null): array
    {
        $rules = match ($resource) {
            'fighters' => [
                'country_id' => ['nullable', 'exists:countries,id'],
                'stance_id' => ['nullable', 'exists:stances,id'],
                'weight_class_id' => ['nullable', 'exists:weight_classes,id'],
                'slug' => ['nullable', 'string', 'max:255', Rule::unique('fighters', 'slug')->ignore($model?->id)],
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'display_name' => ['required', 'string', 'max:255'],
                'ring_name' => ['nullable', 'string', 'max:255'],
                'birth_date' => ['nullable', 'date'],
                'birth_place' => ['nullable', 'string', 'max:255'],
                'residence' => ['nullable', 'string', 'max:255'],
                'height_cm' => ['nullable', 'integer', 'min:1', 'max:260'],
                'reach_cm' => ['nullable', 'integer', 'min:1', 'max:280'],
                'wins' => ['required', 'integer', 'min:0'],
                'losses' => ['required', 'integer', 'min:0'],
                'draws' => ['required', 'integer', 'min:0'],
                'no_contests' => ['nullable', 'integer', 'min:0'],
                'knockouts' => ['required', 'integer', 'min:0'],
                'debut_date' => ['nullable', 'date'],
                'active' => ['boolean'],
                'photo_url' => ['nullable', 'url', 'max:2048'],
                'bio' => ['nullable', 'string'],
            ],
            'events' => [
                'venue_id' => ['nullable', 'exists:venues,id'],
                'promoter_id' => ['nullable', 'exists:promoters,id'],
                'slug' => ['nullable', 'string', 'max:255', Rule::unique('events', 'slug')->ignore($model?->id)],
                'name' => ['required', 'string', 'max:255'],
                'subtitle' => ['nullable', 'string', 'max:255'],
                'event_date' => ['required', 'date'],
                'ring_walks_at' => ['nullable', 'date'],
                'status' => ['required', Rule::in(['upcoming', 'completed', 'cancelled'])],
                'poster_url' => ['nullable', 'url', 'max:2048'],
                'hero_image_url' => ['nullable', 'url', 'max:2048'],
                'broadcast_notes' => ['nullable', 'string', 'max:255'],
                'ticket_url' => ['nullable', 'url', 'max:2048'],
                'fights' => ['sometimes', 'array'],
                'fights.*.id' => ['nullable', 'integer', 'exists:fights,id'],
                'fights.*.red_corner_fighter_id' => ['required_with:fights', 'integer', 'exists:fighters,id'],
                'fights.*.blue_corner_fighter_id' => ['required_with:fights', 'integer', 'exists:fighters,id'],
                'fights.*.winner_fighter_id' => ['nullable', 'integer', 'exists:fighters,id'],
                'fights.*.result_method_id' => ['nullable', 'integer', 'exists:result_methods,id'],
                'fights.*.weight_class_id' => ['nullable', 'integer', 'exists:weight_classes,id'],
                'fights.*.title' => ['nullable', 'string', 'max:255'],
                'fights.*.billing' => ['required_with:fights', Rule::in(['main_event', 'co_main_event', 'undercard'])],
                'fights.*.bout_order' => ['required_with:fights', 'integer', 'min:1'],
                'fights.*.scheduled_rounds' => ['required_with:fights', 'integer', 'min:1', 'max:15'],
                'fights.*.completed_rounds' => ['nullable', 'integer', 'min:1', 'max:15'],
                'fights.*.result_time' => ['nullable', 'string', 'max:10'],
                'fights.*.is_title_fight' => ['boolean'],
                'fights.*.status' => ['required_with:fights', Rule::in(['scheduled', 'completed', 'cancelled'])],
                'fights.*.result_notes' => ['nullable', 'string', 'max:255'],
            ],
            'fights' => [
                'event_id' => ['required', 'exists:events,id'],
                'weight_class_id' => ['nullable', 'exists:weight_classes,id'],
                'red_corner_fighter_id' => ['required', 'exists:fighters,id'],
                'blue_corner_fighter_id' => ['required', 'different:red_corner_fighter_id', 'exists:fighters,id'],
                'winner_fighter_id' => ['nullable', 'exists:fighters,id'],
                'result_method_id' => ['nullable', 'exists:result_methods,id'],
                'title' => ['nullable', 'string', 'max:255'],
                'billing' => ['required', Rule::in(['main_event', 'co_main_event', 'undercard'])],
                'bout_order' => ['required', 'integer', 'min:1'],
                'scheduled_rounds' => ['required', 'integer', 'min:1', 'max:15'],
                'completed_rounds' => ['nullable', 'integer', 'min:1', 'max:15'],
                'result_time' => ['nullable', 'string', 'max:10'],
                'is_title_fight' => ['boolean'],
                'status' => ['required', Rule::in(['scheduled', 'completed', 'cancelled'])],
                'fight_date' => ['nullable', 'date'],
                'result_notes' => ['nullable', 'string', 'max:255'],
            ],
            'promoters' => [
                'country_id' => ['nullable', 'exists:countries,id'],
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['nullable', 'string', 'max:255', Rule::unique('promoters', 'slug')->ignore($model?->id)],
                'website_url' => ['nullable', 'url', 'max:2048'],
            ],
            'venues' => [
                'country_id' => ['nullable', 'exists:countries,id'],
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['nullable', 'string', 'max:255', Rule::unique('venues', 'slug')->ignore($model?->id)],
                'city' => ['required', 'string', 'max:255'],
                'region' => ['nullable', 'string', 'max:255'],
                'address' => ['nullable', 'string', 'max:255'],
                'capacity' => ['nullable', 'integer', 'min:0'],
            ],
            'weight-classes' => [
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['nullable', 'string', 'max:255', Rule::unique('weight_classes', 'slug')->ignore($model?->id)],
                'limit_pounds' => ['nullable', 'integer', 'min:1'],
                'limit_kg' => ['nullable', 'numeric', 'min:0'],
                'sort_order' => ['nullable', 'integer', 'min:0'],
            ],
            'organisations' => [
                'name' => ['required', 'string', 'max:255'],
                'abbreviation' => ['required', 'string', 'max:20'],
                'slug' => ['nullable', 'string', 'max:255', Rule::unique('organisations', 'slug')->ignore($model?->id)],
                'logo_url' => ['nullable', 'url', 'max:2048'],
            ],
            'belts' => [
                'organisation_id' => ['required', 'exists:organisations,id'],
                'weight_class_id' => ['required', 'exists:weight_classes,id'],
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['nullable', 'string', 'max:255', Rule::unique('belts', 'slug')->ignore($model?->id)],
                'active' => ['boolean'],
                'current_champion_id' => ['nullable', 'exists:fighters,id'],
                'reign_started_on' => ['nullable', 'date'],
                'reign_result' => ['nullable', 'string', 'max:255'],
            ],
            'belt-history' => [
                'belt_id' => ['required', 'exists:belts,id'],
                'fighter_id' => ['required', 'exists:fighters,id'],
                'fight_id' => ['nullable', 'exists:fights,id'],
                'event_id' => ['nullable', 'exists:events,id'],
                'reign_started_on' => ['required', 'date'],
                'reign_ended_on' => ['nullable', 'date'],
                'status' => ['required', Rule::in(['current', 'former', 'vacated'])],
                'result' => ['nullable', 'string', 'max:255'],
            ],
            'rankings' => [
                'organisation_id' => ['required', 'exists:organisations,id'],
                'weight_class_id' => ['required', 'exists:weight_classes,id'],
                'fighter_id' => ['required', 'exists:fighters,id'],
                'rank' => ['required', 'integer', 'min:1', 'max:100'],
                'points' => ['nullable', 'integer', 'min:0'],
                'ranked_on' => ['required', 'date'],
            ],
            'broadcasters' => [
                'country_id' => ['nullable', 'exists:countries,id'],
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['nullable', 'string', 'max:255', Rule::unique('broadcasters', 'slug')->ignore($model?->id)],
                'logo_url' => ['nullable', 'url', 'max:2048'],
                'website_url' => ['nullable', 'url', 'max:2048'],
            ],
            'event-broadcasts' => [
                'event_id' => ['required', 'exists:events,id'],
                'broadcaster_id' => ['required', 'exists:broadcasters,id'],
                'region' => ['required', 'string', 'max:255'],
                'platform' => ['nullable', 'string', 'max:255'],
                'is_ppv' => ['boolean'],
                'details' => ['nullable', 'string', 'max:255'],
            ],
            'media' => [
                'mediable_type' => ['required', Rule::in(array_keys(self::MEDIA_TYPES))],
                'mediable_id' => ['required', 'integer'],
                'collection' => ['required', 'string', 'max:255'],
                'title' => ['nullable', 'string', 'max:255'],
                'url' => ['required', 'url', 'max:2048'],
                'credit' => ['nullable', 'string', 'max:255'],
                'sort_order' => ['nullable', 'integer', 'min:0'],
            ],
            default => [],
        };

        $validator = Validator::make($request->all(), $rules);
        $validator->after(function ($validator) use ($request, $resource) {
            if ($resource === 'media') {
                $class = self::MEDIA_TYPES[$request->input('mediable_type')] ?? null;
                if ($class && !$class::query()->whereKey($request->input('mediable_id'))->exists()) {
                    $validator->errors()->add('mediable_id', 'The selected media parent does not exist.');
                }
            }

            if ($resource === 'events') {
                foreach ((array) $request->input('fights', []) as $index => $fight) {
                    if (($fight['red_corner_fighter_id'] ?? null) && ($fight['red_corner_fighter_id'] ?? null) === ($fight['blue_corner_fighter_id'] ?? null)) {
                        $validator->errors()->add("fights.$index.blue_corner_fighter_id", 'Fighter B must be different from Fighter A.');
                    }
                }
            }
        });

        return $validator->validate();
    }

    private function makeModel(string $resource, array &$data): Model
    {
        $config = $this->resourceConfig($resource);

        if ($resource === 'media') {
            $data['mediable_type'] = self::MEDIA_TYPES[$data['mediable_type']];
        }

        return new $config['model']();
    }

    private function payloadForModel(string $resource, array $data): array
    {
        $payload = Arr::except($data, ['fights', 'current_champion_id', 'reign_started_on', 'reign_result']);

        if (isset($payload['slug']) && $payload['slug'] === '') {
            unset($payload['slug']);
        }

        if (empty($payload['slug']) && isset($payload['name'])) {
            $payload['slug'] = Str::slug($payload['name']);
        }

        if ($resource === 'fighters' && empty($payload['slug'])) {
            $payload['slug'] = Str::slug($payload['display_name']);
        }

        if ($resource === 'media' && isset($payload['mediable_type'])) {
            $payload['mediable_type'] = self::MEDIA_TYPES[$payload['mediable_type']] ?? $payload['mediable_type'];
        }

        return $payload;
    }

    private function afterSave(string $resource, Model $model, array $data): void
    {
        if ($resource === 'events' && array_key_exists('fights', $data)) {
            $this->syncEventFights($model, $data['fights'] ?? []);
        }

        if ($resource === 'belts' && !empty($data['current_champion_id'])) {
            BeltHistory::query()
                ->where('belt_id', $model->id)
                ->where('status', 'current')
                ->where('fighter_id', '!=', $data['current_champion_id'])
                ->update([
                    'status' => 'former',
                    'reign_ended_on' => now()->toDateString(),
                ]);

            BeltHistory::updateOrCreate([
                'belt_id' => $model->id,
                'fighter_id' => $data['current_champion_id'],
                'status' => 'current',
            ], [
                'reign_started_on' => $data['reign_started_on'] ?? now()->toDateString(),
                'reign_ended_on' => null,
                'result' => $data['reign_result'] ?? 'Current champion',
            ]);
        }
    }

    private function syncEventFights(Event $event, array $fights): void
    {
        $keptIds = [];

        foreach ($fights as $row) {
            $payload = [
                'event_id' => $event->id,
                'weight_class_id' => $row['weight_class_id'] ?? null,
                'red_corner_fighter_id' => $row['red_corner_fighter_id'],
                'blue_corner_fighter_id' => $row['blue_corner_fighter_id'],
                'winner_fighter_id' => $row['winner_fighter_id'] ?? null,
                'result_method_id' => $row['result_method_id'] ?? null,
                'title' => $row['title'] ?? null,
                'billing' => $row['billing'] ?? 'undercard',
                'bout_order' => $row['bout_order'] ?? 1,
                'scheduled_rounds' => $row['scheduled_rounds'] ?? 12,
                'completed_rounds' => $row['completed_rounds'] ?? null,
                'result_time' => $row['result_time'] ?? null,
                'is_title_fight' => (bool) ($row['is_title_fight'] ?? false),
                'status' => $row['status'] ?? 'scheduled',
                'fight_date' => $event->event_date,
                'result_notes' => $row['result_notes'] ?? null,
            ];

            $fight = !empty($row['id'])
                ? Fight::query()->where('event_id', $event->id)->findOrFail($row['id'])
                : new Fight();

            $fight->fill($payload);
            $fight->save();
            $keptIds[] = $fight->id;
        }

        $query = Fight::query()->where('event_id', $event->id);

        if (count($keptIds) > 0) {
            $query->whereNotIn('id', $keptIds);
        }

        $query->delete();
    }

    private function serialize(string $resource, Model $model): array
    {
        $data = $model->toArray();

        if ($resource === 'media') {
            $data['mediable_type'] = array_search($model->mediable_type, self::MEDIA_TYPES, true) ?: $model->mediable_type;
            $data['mediable_label'] = match ($model->mediable_type) {
                Fighter::class => $model->mediable?->display_name,
                Event::class => $model->mediable?->name,
                default => null,
            };
        }

        if ($resource === 'belts') {
            $current = $model->relationLoaded('currentReign') ? $model->currentReign->first() : null;
            $data['current_champion_id'] = $current?->fighter_id;
            $data['reign_started_on'] = $current?->reign_started_on?->toDateString();
            $data['reign_result'] = $current?->result;
        }

        return $data;
    }
}
