<?php

namespace App\Console\Commands;

use App\Models\Belt;
use App\Models\BeltHistory;
use App\Models\Broadcaster;
use App\Models\Country;
use App\Models\Event;
use App\Models\EventBroadcast;
use App\Models\Fight;
use App\Models\Fighter;
use App\Models\FighterAlias;
use App\Models\Media;
use App\Models\Organisation;
use App\Models\Promoter;
use App\Models\Ranking;
use App\Models\ResultMethod;
use App\Models\Stance;
use App\Models\Venue;
use App\Models\WeightClass;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class ImportBoxingScrape extends Command
{
    protected $signature = 'boxingdb:import-scraped
        {path : Path to normalized scraper JSON}
        {--dry-run : Parse and upsert inside a rolled-back transaction}
        {--replace-event-fights : Delete existing event fights before importing scraped fight cards}';

    protected $description = 'Import normalized Selenium scraper output into BoxingDB tables.';

    private array $stats = [];

    private array $warnings = [];

    public function handle(): int
    {
        $path = $this->resolvePath((string) $this->argument('path'));

        if (! is_file($path)) {
            $this->error("Import file not found: {$path}");

            return self::FAILURE;
        }

        $payload = json_decode((string) file_get_contents($path), true);

        if (! is_array($payload)) {
            $this->error('Import file is not valid JSON.');

            return self::FAILURE;
        }

        DB::beginTransaction();

        try {
            $this->importBroadcasters($payload['broadcasters'] ?? []);
            $this->importFighters($payload['fighters'] ?? []);
            $this->importEvents($payload['events'] ?? []);
            $this->importStandaloneFights($payload['fights'] ?? []);
            $this->importBelts($payload['belts'] ?? []);
            $this->importRankings($payload['rankings'] ?? []);
            $this->importEventBroadcasts($payload['event_broadcasts'] ?? []);
            $this->importMedia($payload['media'] ?? []);

            if ($this->option('dry-run')) {
                DB::rollBack();
                $this->warn('Dry run complete. No database changes were committed.');
            } else {
                DB::commit();
                $this->info('BoxingDB scrape import complete.');
            }
        } catch (Throwable $exception) {
            DB::rollBack();
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->printSummary();

        return self::SUCCESS;
    }

    private function importBroadcasters(array $rows): void
    {
        foreach ($rows as $row) {
            if (! is_array($row) || ! $this->string($row['name'] ?? null)) {
                continue;
            }

            $this->broadcaster($row);
            $this->bump('broadcasters');
        }
    }

    private function importFighters(array $rows): void
    {
        foreach ($rows as $row) {
            if (is_array($row)) {
                $this->importFighter($row);
            }
        }
    }

    private function importEvents(array $rows): void
    {
        foreach ($rows as $row) {
            if (is_array($row)) {
                $this->importEvent($row);
            }
        }
    }

    private function importStandaloneFights(array $rows): void
    {
        foreach ($rows as $index => $row) {
            if (is_array($row)) {
                $this->importFight($row, null, $index + 1);
            }
        }
    }

    private function importBelts(array $rows): void
    {
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $organisation = $this->organisation($row['organisation'] ?? $row['organisation_abbreviation'] ?? null);
            $weightClass = $this->weightClass($row['weight_class'] ?? null);

            if (! $organisation || ! $weightClass) {
                $this->warnRow('Skipping belt without organisation or weight class.', $row);
                continue;
            }

            $name = $this->string($row['name'] ?? null) ?: "{$organisation->abbreviation} {$weightClass->name} World Title";
            $belt = Belt::updateOrCreate([
                'organisation_id' => $organisation->id,
                'weight_class_id' => $weightClass->id,
            ], $this->clean([
                'name' => $name,
                'slug' => $this->slug($row['slug'] ?? $name),
                'active' => $this->bool($row['active'] ?? true),
            ]));

            $champion = $this->fighterRef($row['current_champion'] ?? $row['current_champion_name'] ?? null);
            if ($champion) {
                BeltHistory::query()
                    ->where('belt_id', $belt->id)
                    ->where('status', 'current')
                    ->where('fighter_id', '!=', $champion->id)
                    ->update([
                        'status' => 'former',
                        'reign_ended_on' => now()->toDateString(),
                    ]);

                BeltHistory::updateOrCreate([
                    'belt_id' => $belt->id,
                    'fighter_id' => $champion->id,
                    'status' => 'current',
                ], $this->clean([
                    'reign_started_on' => $this->date($row['reign_started_on'] ?? null) ?: now()->toDateString(),
                    'reign_ended_on' => null,
                    'result' => $this->string($row['result'] ?? null) ?: 'Current champion',
                ]));
            }

            $this->bump('belts');
        }
    }

    private function importRankings(array $rows): void
    {
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $organisation = $this->organisation($row['organisation'] ?? null);
            $weightClass = $this->weightClass($row['weight_class'] ?? null);
            $rankedOn = $this->date($row['ranked_on'] ?? null) ?: now()->toDateString();

            if (! $organisation || ! $weightClass) {
                $this->warnRow('Skipping ranking without organisation or weight class.', $row);
                continue;
            }

            foreach (($row['entries'] ?? []) as $index => $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $fighter = $this->fighterRef($entry['fighter'] ?? $entry['name'] ?? null);
                if (! $fighter) {
                    continue;
                }

                Ranking::updateOrCreate([
                    'organisation_id' => $organisation->id,
                    'weight_class_id' => $weightClass->id,
                    'fighter_id' => $fighter->id,
                    'ranked_on' => $rankedOn,
                ], [
                    'rank' => $this->int($entry['rank'] ?? null) ?: $index + 1,
                    'points' => $this->int($entry['points'] ?? null) ?: 0,
                ]);

                $this->bump('rankings');
            }
        }
    }

    private function importEventBroadcasts(array $rows): void
    {
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $event = $this->eventRef($row['event'] ?? $row['event_slug'] ?? null);
            $broadcaster = $this->broadcaster($row['broadcaster'] ?? null);

            if (! $event || ! $broadcaster) {
                $this->warnRow('Skipping event broadcast without event or broadcaster.', $row);
                continue;
            }

            EventBroadcast::updateOrCreate([
                'event_id' => $event->id,
                'broadcaster_id' => $broadcaster->id,
                'region' => $this->string($row['region'] ?? null) ?: 'Global',
            ], $this->clean([
                'platform' => $this->string($row['platform'] ?? null),
                'is_ppv' => $this->bool($row['is_ppv'] ?? false),
                'details' => $this->string($row['details'] ?? null),
            ]));

            $this->bump('event_broadcasts');
        }
    }

    private function importMedia(array $rows): void
    {
        foreach ($rows as $row) {
            if (! is_array($row) || ! $this->string($row['url'] ?? null)) {
                continue;
            }

            $type = $this->string($row['type'] ?? $row['mediable_type'] ?? null) ?: 'event';
            $parent = $row['parent'] ?? $row['mediable'] ?? $row['mediable_slug'] ?? null;
            $model = $type === 'fighter' ? $this->fighterRef($parent) : $this->eventRef($parent);

            if (! $model) {
                $this->warnRow('Skipping media without a matching fighter or event parent.', $row);
                continue;
            }

            Media::updateOrCreate([
                'mediable_type' => get_class($model),
                'mediable_id' => $model->id,
                'url' => $this->string($row['url']),
            ], $this->clean([
                'collection' => $this->string($row['collection'] ?? null) ?: 'gallery',
                'title' => $this->string($row['title'] ?? null),
                'credit' => $this->string($row['credit'] ?? null),
                'sort_order' => $this->int($row['sort_order'] ?? null) ?: 0,
            ]));

            $this->bump('media');
        }
    }

    private function importFighter(array $row): ?Fighter
    {
        $displayName = $this->string($row['display_name'] ?? $row['name'] ?? null);

        if (! $displayName || $this->isPlaceholderName($displayName)) {
            return null;
        }

        [$firstName, $lastName] = $this->splitName($displayName);
        $record = is_array($row['record'] ?? null) ? $row['record'] : null;
        $hasRecord = $record !== null || isset($row['wins'], $row['losses']);

        $payload = [
            'country_id' => $this->country($row['country'] ?? $row['nationality'] ?? null)?->id,
            'stance_id' => $this->stance($row['stance'] ?? null)?->id,
            'weight_class_id' => $this->weightClass($row['weight_class'] ?? null)?->id,
            'first_name' => $this->string($row['first_name'] ?? null) ?: $firstName,
            'last_name' => $this->string($row['last_name'] ?? null) ?: $lastName,
            'display_name' => $displayName,
            'ring_name' => $this->string($row['ring_name'] ?? null),
            'birth_date' => $this->date($row['birth_date'] ?? null),
            'birth_place' => $this->string($row['birth_place'] ?? null),
            'residence' => $this->string($row['residence'] ?? null),
            'height_cm' => $this->int($row['height_cm'] ?? null),
            'reach_cm' => $this->int($row['reach_cm'] ?? null),
            'debut_date' => $this->date($row['debut_date'] ?? null),
            'active' => $this->bool($row['active'] ?? true),
            'photo_url' => $this->string($row['photo_url'] ?? null),
            'bio' => $this->string($row['bio'] ?? null),
        ];

        if ($hasRecord) {
            $payload += [
                'wins' => $this->int($row['wins'] ?? $record['wins'] ?? null) ?: 0,
                'losses' => $this->int($row['losses'] ?? $record['losses'] ?? null) ?: 0,
                'draws' => $this->int($row['draws'] ?? $record['draws'] ?? null) ?: 0,
                'no_contests' => $this->int($row['no_contests'] ?? $record['no_contests'] ?? null) ?: 0,
                'knockouts' => $this->int($row['knockouts'] ?? $record['knockouts'] ?? null) ?: 0,
            ];
        }

        $fighter = Fighter::updateOrCreate([
            'slug' => $this->slug($row['slug'] ?? $displayName),
        ], $this->clean($payload));

        foreach ($this->aliases($row['aliases'] ?? []) as $alias) {
            FighterAlias::updateOrCreate([
                'fighter_id' => $fighter->id,
                'alias' => $alias,
            ]);
        }

        $this->bump('fighters');

        return $fighter;
    }

    private function importEvent(array $row): ?Event
    {
        $name = $this->string($row['name'] ?? null);
        $eventDate = $this->dateTime($row['event_date'] ?? null);

        if (! $name || ! $eventDate) {
            $this->warnRow('Skipping event without name or event_date.', $row);

            return null;
        }

        $event = Event::updateOrCreate([
            'slug' => $this->slug($row['slug'] ?? $name),
        ], $this->clean([
            'venue_id' => $this->venue($row['venue'] ?? null)?->id,
            'promoter_id' => $this->promoter($row['promoter'] ?? null)?->id,
            'name' => $name,
            'subtitle' => $this->string($row['subtitle'] ?? null),
            'event_date' => $eventDate,
            'ring_walks_at' => $this->dateTime($row['ring_walks_at'] ?? null),
            'status' => $this->eventStatus($row['status'] ?? null, $eventDate),
            'poster_url' => $this->string($row['poster_url'] ?? null),
            'hero_image_url' => $this->string($row['hero_image_url'] ?? null),
            'broadcast_notes' => $this->string($row['broadcast_notes'] ?? null),
            'ticket_url' => $this->string($row['ticket_url'] ?? null),
        ]));

        if ($this->option('replace-event-fights') && is_array($row['fights'] ?? null)) {
            $event->fights()->delete();
        }

        foreach (($row['fights'] ?? []) as $index => $fight) {
            if (is_array($fight)) {
                $fight['fight_date'] ??= $eventDate;
                $this->importFight($fight, $event, $index + 1);
            }
        }

        $this->bump('events');

        return $event;
    }

    private function importFight(array $row, ?Event $event = null, ?int $order = null): ?Fight
    {
        $event ??= $this->eventRef($row['event'] ?? $row['event_slug'] ?? null);
        $red = $this->fighterRef($row['red_corner'] ?? $row['fighter_a'] ?? $row['red_corner_fighter'] ?? null);
        $blue = $this->fighterRef($row['blue_corner'] ?? $row['fighter_b'] ?? $row['blue_corner_fighter'] ?? null);

        if (! $event || ! $red || ! $blue || $red->is($blue)) {
            $this->warnRow('Skipping fight without event and two different fighters.', $row);

            return null;
        }

        $winner = $this->winner($row['winner'] ?? null, $red, $blue);
        $method = $this->resultMethod($row['method'] ?? $row['result_method'] ?? null);

        $payload = [
            'weight_class_id' => $this->weightClass($row['weight_class'] ?? null)?->id,
            'winner_fighter_id' => $winner?->id,
            'result_method_id' => $method?->id,
            'title' => $this->string($row['title'] ?? $row['stakes'] ?? null),
            'billing' => $this->billing($row['billing'] ?? null, $order),
            'bout_order' => $this->int($row['bout_order'] ?? null) ?: $order ?: 1,
            'scheduled_rounds' => $this->int($row['scheduled_rounds'] ?? null) ?: 12,
            'completed_rounds' => $this->int($row['completed_rounds'] ?? null),
            'is_title_fight' => $this->bool($row['is_title_fight'] ?? false),
            'status' => $this->fightStatus($row['status'] ?? null, $winner, $method),
            'fight_date' => $this->dateTime($row['fight_date'] ?? null) ?: $event->event_date,
            'result_notes' => $this->string($row['result_notes'] ?? null),
        ];

        if (Schema::hasColumn('fights', 'result_time')) {
            $payload['result_time'] = $this->string($row['result_time'] ?? null);
        }

        $fight = Fight::updateOrCreate([
            'event_id' => $event->id,
            'red_corner_fighter_id' => $red->id,
            'blue_corner_fighter_id' => $blue->id,
        ], $this->clean($payload));

        $this->bump('fights');

        return $fight;
    }

    private function country(mixed $value): ?Country
    {
        [$name, $code] = $this->countryNameAndCode($value);

        if (! $name && ! $code) {
            return null;
        }

        $query = Country::query();
        if ($code) {
            $query->where('code', $code);
        }
        if ($name) {
            $query->orWhere('name', $name);
        }

        $country = $query->first();
        if ($country) {
            return $country;
        }

        $name ??= $this->countryNameFromCode($code) ?: $code;
        $code ??= $this->countryCode($name);

        return Country::create([
            'name' => $name,
            'code' => $code,
        ]);
    }

    private function stance(mixed $value): ?Stance
    {
        $name = $this->string($value);
        if (! $name) {
            return null;
        }

        return Stance::updateOrCreate([
            'slug' => $this->slug($name),
        ], [
            'name' => Str::title($name),
        ]);
    }

    private function weightClass(mixed $value): ?WeightClass
    {
        $name = $this->string($value);
        if (! $name) {
            return null;
        }

        $name = $this->weightClassName($name);
        $limits = $this->weightClassLimits($name);

        return WeightClass::updateOrCreate([
            'slug' => $this->slug($name),
        ], [
            'name' => $name,
            'limit_pounds' => $limits['pounds'],
            'limit_kg' => $limits['kg'],
            'sort_order' => $limits['sort'],
        ]);
    }

    private function organisation(mixed $value): ?Organisation
    {
        $name = is_array($value) ? $this->string($value['name'] ?? null) : $this->string($value);
        $abbr = is_array($value) ? $this->string($value['abbreviation'] ?? null) : null;

        if (! $name && ! $abbr) {
            return null;
        }

        $abbr ??= Str::upper($name);
        $known = [
            'WBC' => ['World Boxing Council', 'WBC'],
            'WORLD BOXING COUNCIL' => ['World Boxing Council', 'WBC'],
            'WBA' => ['World Boxing Association', 'WBA'],
            'WORLD BOXING ASSOCIATION' => ['World Boxing Association', 'WBA'],
            'IBF' => ['International Boxing Federation', 'IBF'],
            'INTERNATIONAL BOXING FEDERATION' => ['International Boxing Federation', 'IBF'],
            'WBO' => ['World Boxing Organization', 'WBO'],
            'WORLD BOXING ORGANIZATION' => ['World Boxing Organization', 'WBO'],
            'RING' => ['The Ring', 'RING'],
            'THE RING' => ['The Ring', 'RING'],
        ];

        $upper = Str::upper($abbr);
        [$name, $abbr] = $known[$upper] ?? [$name ?? $abbr, Str::substr($upper, 0, 20)];

        $organisation = Organisation::query()
            ->where('abbreviation', $abbr)
            ->orWhere('name', $name)
            ->first();

        $payload = [
            'name' => $name,
            'abbreviation' => $abbr,
            'slug' => $organisation?->slug ?: $this->slug($abbr),
        ];

        if ($organisation) {
            $organisation->fill($payload);
            $organisation->save();

            return $organisation;
        }

        return Organisation::create($payload);
    }

    private function promoter(mixed $value): ?Promoter
    {
        $name = is_array($value) ? $this->string($value['name'] ?? null) : $this->string($value);
        if (! $name) {
            return null;
        }
        $slugSeed = is_array($value) ? ($value['slug'] ?? $name) : $name;

        return Promoter::updateOrCreate([
            'slug' => $this->slug($slugSeed),
        ], $this->clean([
            'name' => $name,
            'country_id' => is_array($value) ? $this->country($value['country'] ?? null)?->id : null,
            'website_url' => is_array($value) ? $this->string($value['website_url'] ?? null) : null,
        ]));
    }

    private function venue(mixed $value): ?Venue
    {
        $name = is_array($value) ? $this->string($value['name'] ?? null) : $this->string($value);
        if (! $name) {
            return null;
        }

        $city = is_array($value) ? $this->string($value['city'] ?? null) : null;
        $city ??= $this->cityFromVenueString($name) ?: 'TBA';
        $slugSeed = is_array($value) ? (($value['slug'] ?? null) ?: "{$name} {$city}") : "{$name} {$city}";

        return Venue::updateOrCreate([
            'slug' => $this->slug($slugSeed),
        ], $this->clean([
            'name' => $name,
            'city' => $city,
            'region' => is_array($value) ? $this->string($value['region'] ?? null) : null,
            'address' => is_array($value) ? $this->string($value['address'] ?? null) : null,
            'capacity' => is_array($value) ? $this->int($value['capacity'] ?? null) : null,
            'country_id' => is_array($value) ? $this->country($value['country'] ?? null)?->id : null,
        ]));
    }

    private function broadcaster(mixed $value): ?Broadcaster
    {
        $name = is_array($value) ? $this->string($value['name'] ?? null) : $this->string($value);
        if (! $name) {
            return null;
        }
        $slugSeed = is_array($value) ? ($value['slug'] ?? $name) : $name;

        return Broadcaster::updateOrCreate([
            'slug' => $this->slug($slugSeed),
        ], $this->clean([
            'name' => $name,
            'country_id' => is_array($value) ? $this->country($value['country'] ?? null)?->id : null,
            'logo_url' => is_array($value) ? $this->string($value['logo_url'] ?? null) : null,
            'website_url' => is_array($value) ? $this->string($value['website_url'] ?? null) : null,
        ]));
    }

    private function resultMethod(mixed $value): ?ResultMethod
    {
        $text = $this->string($value);
        if (! $text) {
            return null;
        }

        $abbr = $this->resultMethodAbbreviation($text);
        $name = $this->resultMethodName($abbr, $text);

        return ResultMethod::updateOrCreate([
            'slug' => $this->slug($abbr),
        ], [
            'name' => $name,
            'abbreviation' => $abbr,
        ]);
    }

    private function fighterRef(mixed $value): ?Fighter
    {
        if (is_array($value)) {
            return $this->importFighter($value);
        }

        $name = $this->string($value);
        if (! $name || $this->isPlaceholderName($name)) {
            return null;
        }

        $slug = $this->slug($name);
        $fighter = Fighter::query()
            ->where('slug', $slug)
            ->orWhere('display_name', $name)
            ->orWhereHas('aliases', fn ($query) => $query->where('alias', $name))
            ->first();

        if ($fighter) {
            return $fighter;
        }

        return $this->importFighter([
            'display_name' => $name,
            'slug' => $slug,
        ]);
    }

    private function eventRef(mixed $value): ?Event
    {
        if (is_array($value)) {
            return $this->importEvent($value);
        }

        $name = $this->string($value);
        if (! $name) {
            return null;
        }

        return Event::query()
            ->where('slug', $this->slug($name))
            ->orWhere('name', $name)
            ->first();
    }

    private function winner(mixed $value, Fighter $red, Fighter $blue): ?Fighter
    {
        $winner = $this->string($value);
        if (! $winner || Str::contains(Str::lower($winner), ['draw', 'no contest', 'nc'])) {
            return null;
        }

        if (Str::lower($winner) === 'red') {
            return $red;
        }

        if (Str::lower($winner) === 'blue') {
            return $blue;
        }

        return $this->fighterRef($winner);
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }

    private function date(mixed $value): ?string
    {
        $date = $this->dateTime($value);

        return $date ? Carbon::parse($date)->toDateString() : null;
    }

    private function dateTime(mixed $value): ?string
    {
        $text = $this->string($value);
        if (! $text) {
            return null;
        }

        try {
            return Carbon::parse($text)->toDateTimeString();
        } catch (Throwable) {
            return null;
        }
    }

    private function string(mixed $value): ?string
    {
        if ($value === null || is_array($value)) {
            return null;
        }

        $value = trim(preg_replace('/\s+/', ' ', (string) $value));

        return $value === '' ? null : $value;
    }

    private function int(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        preg_match('/\d+/', (string) $value, $matches);

        return isset($matches[0]) ? (int) $matches[0] : null;
    }

    private function bool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function slug(mixed $value): string
    {
        return Str::slug($this->string($value) ?: Str::random(8));
    }

    private function clean(array $payload): array
    {
        return array_filter($payload, fn ($value) => $value !== null && $value !== '');
    }

    private function aliases(mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(fn ($alias) => $this->string($alias), $value)));
    }

    private function splitName(string $displayName): array
    {
        $parts = explode(' ', $displayName);

        if (count($parts) === 1) {
            return [$displayName, 'Unknown'];
        }

        return [implode(' ', array_slice($parts, 0, -1)), end($parts)];
    }

    private function eventStatus(?string $status, string $eventDate): string
    {
        $status = Str::lower($status ?: '');
        if (in_array($status, ['upcoming', 'completed', 'cancelled'], true)) {
            return $status;
        }

        return Carbon::parse($eventDate)->isPast() ? 'completed' : 'upcoming';
    }

    private function fightStatus(?string $status, ?Fighter $winner, ?ResultMethod $method): string
    {
        $status = Str::lower($status ?: '');
        if (in_array($status, ['scheduled', 'completed', 'cancelled'], true)) {
            return $status;
        }

        return $winner || $method ? 'completed' : 'scheduled';
    }

    private function billing(?string $billing, ?int $order): string
    {
        $billing = Str::lower((string) $billing);
        $billing = str_replace([' ', '-'], '_', $billing);

        if (in_array($billing, ['main_event', 'co_main_event', 'undercard'], true)) {
            return $billing;
        }

        return $order === 1 ? 'main_event' : ($order === 2 ? 'co_main_event' : 'undercard');
    }

    private function resultMethodAbbreviation(string $text): string
    {
        $upper = Str::upper($text);

        if (Str::contains($upper, 'TECHNICAL') || Str::contains($upper, 'TKO')) {
            return 'TKO';
        }
        if (Str::contains($upper, 'UNANIMOUS') || $upper === 'UD') {
            return 'UD';
        }
        if (Str::contains($upper, 'MAJORITY') || $upper === 'MD') {
            return 'MD';
        }
        if (Str::contains($upper, 'SPLIT') || $upper === 'SD') {
            return 'SD';
        }
        if (Str::contains($upper, 'DRAW')) {
            return 'DRAW';
        }
        if (Str::contains($upper, 'KO') || Str::contains($upper, 'KNOCKOUT')) {
            return 'KO';
        }

        return Str::substr(preg_replace('/[^A-Z0-9]/', '', $upper), 0, 20) ?: 'RESULT';
    }

    private function resultMethodName(string $abbr, string $fallback): string
    {
        return [
            'KO' => 'Knockout',
            'TKO' => 'Technical Knockout',
            'UD' => 'Unanimous Decision',
            'MD' => 'Majority Decision',
            'SD' => 'Split Decision',
            'DRAW' => 'Draw',
        ][$abbr] ?? Str::title($fallback);
    }

    private function weightClassName(string $name): string
    {
        $key = Str::of($name)->lower()->replaceMatches('/[^a-z ]+/', '')->squish()->toString();

        return [
            'heavy' => 'Heavyweight',
            'heavyweight' => 'Heavyweight',
            'cruiser' => 'Cruiserweight',
            'cruiserweight' => 'Cruiserweight',
            'light heavy' => 'Light Heavyweight',
            'light heavyweight' => 'Light Heavyweight',
            'super middle' => 'Super Middleweight',
            'super middleweight' => 'Super Middleweight',
            'middle' => 'Middleweight',
            'middleweight' => 'Middleweight',
            'super welter' => 'Super Welterweight',
            'light middleweight' => 'Super Welterweight',
            'welter' => 'Welterweight',
            'welterweight' => 'Welterweight',
            'super lightweight' => 'Super Lightweight',
            'junior welterweight' => 'Super Lightweight',
            'lightweight' => 'Lightweight',
            'super featherweight' => 'Super Featherweight',
            'junior lightweight' => 'Super Featherweight',
            'featherweight' => 'Featherweight',
            'super bantamweight' => 'Super Bantamweight',
            'junior featherweight' => 'Super Bantamweight',
            'bantamweight' => 'Bantamweight',
            'super flyweight' => 'Super Flyweight',
            'junior bantamweight' => 'Super Flyweight',
            'flyweight' => 'Flyweight',
            'light flyweight' => 'Light Flyweight',
            'minimumweight' => 'Minimumweight',
            'strawweight' => 'Minimumweight',
        ][$key] ?? Str::title($name);
    }

    private function weightClassLimits(string $name): array
    {
        return [
            'Heavyweight' => ['pounds' => null, 'kg' => null, 'sort' => 1],
            'Bridgerweight' => ['pounds' => 224, 'kg' => 101.6, 'sort' => 2],
            'Cruiserweight' => ['pounds' => 200, 'kg' => 90.72, 'sort' => 3],
            'Light Heavyweight' => ['pounds' => 175, 'kg' => 79.38, 'sort' => 4],
            'Super Middleweight' => ['pounds' => 168, 'kg' => 76.2, 'sort' => 5],
            'Middleweight' => ['pounds' => 160, 'kg' => 72.57, 'sort' => 6],
            'Super Welterweight' => ['pounds' => 154, 'kg' => 69.85, 'sort' => 7],
            'Welterweight' => ['pounds' => 147, 'kg' => 66.68, 'sort' => 8],
            'Super Lightweight' => ['pounds' => 140, 'kg' => 63.5, 'sort' => 9],
            'Lightweight' => ['pounds' => 135, 'kg' => 61.23, 'sort' => 10],
            'Super Featherweight' => ['pounds' => 130, 'kg' => 58.97, 'sort' => 11],
            'Featherweight' => ['pounds' => 126, 'kg' => 57.15, 'sort' => 12],
            'Super Bantamweight' => ['pounds' => 122, 'kg' => 55.34, 'sort' => 13],
            'Bantamweight' => ['pounds' => 118, 'kg' => 53.52, 'sort' => 14],
            'Super Flyweight' => ['pounds' => 115, 'kg' => 52.16, 'sort' => 15],
            'Flyweight' => ['pounds' => 112, 'kg' => 50.8, 'sort' => 16],
            'Light Flyweight' => ['pounds' => 108, 'kg' => 48.99, 'sort' => 17],
            'Minimumweight' => ['pounds' => 105, 'kg' => 47.63, 'sort' => 18],
        ][$name] ?? ['pounds' => null, 'kg' => null, 'sort' => 99];
    }

    private function countryNameAndCode(mixed $value): array
    {
        if (is_array($value)) {
            return [$this->string($value['name'] ?? null), $this->countryCode($value['code'] ?? null)];
        }

        $text = $this->string($value);
        if (! $text) {
            return [null, null];
        }

        $upper = Str::upper($text);
        if (strlen($upper) <= 3) {
            return [$this->countryNameFromCode($upper), $this->countryCode($upper)];
        }

        return [$text, $this->countryCode($text)];
    }

    private function countryCode(?string $value): ?string
    {
        $value = $this->string($value);
        if (! $value) {
            return null;
        }

        $upper = Str::upper($value);
        $known = [
            'UK' => 'GBR',
            'UNITED KINGDOM' => 'GBR',
            'GREAT BRITAIN' => 'GBR',
            'BRITAIN' => 'GBR',
            'ENGLAND' => 'GBR',
            'SCOTLAND' => 'GBR',
            'WALES' => 'GBR',
            'UNITED STATES' => 'USA',
            'UNITED STATES OF AMERICA' => 'USA',
            'AMERICA' => 'USA',
            'MEXICO' => 'MEX',
            'JAPAN' => 'JPN',
            'UKRAINE' => 'UKR',
            'RUSSIA' => 'RUS',
            'SAUDI ARABIA' => 'SAU',
            'CHINA' => 'CHN',
            'AUSTRALIA' => 'AUS',
            'NEW ZEALAND' => 'NZL',
        ];

        if (isset($known[$upper])) {
            return $known[$upper];
        }

        return Str::substr(preg_replace('/[^A-Z]/', '', $upper), 0, 3) ?: null;
    }

    private function countryNameFromCode(?string $code): ?string
    {
        return [
            'GBR' => 'United Kingdom',
            'USA' => 'United States',
            'MEX' => 'Mexico',
            'JPN' => 'Japan',
            'UKR' => 'Ukraine',
            'RUS' => 'Russia',
            'SAU' => 'Saudi Arabia',
            'CHN' => 'China',
            'AUS' => 'Australia',
            'NZL' => 'New Zealand',
        ][Str::upper((string) $code)] ?? null;
    }

    private function cityFromVenueString(string $venue): ?string
    {
        $parts = array_map('trim', explode(',', $venue));

        return count($parts) > 1 ? end($parts) : null;
    }

    private function isPlaceholderName(string $name): bool
    {
        return in_array(Str::lower($name), ['tba', 'tbd', 'opponent tba', 'to be announced'], true);
    }

    private function bump(string $key): void
    {
        $this->stats[$key] = ($this->stats[$key] ?? 0) + 1;
    }

    private function warnRow(string $message, array $row): void
    {
        $label = $this->string($row['name'] ?? $row['display_name'] ?? $row['source_url'] ?? null);
        $this->warnings[] = $label ? "{$message} ({$label})" : $message;
    }

    private function printSummary(): void
    {
        $rows = collect($this->stats)
            ->sortKeys()
            ->map(fn ($count, $metric) => [$metric, $count])
            ->values()
            ->all();

        if ($rows) {
            $this->table(['Metric', 'Count'], $rows);
        }

        foreach (array_slice($this->warnings, 0, 20) as $warning) {
            $this->warn($warning);
        }

        if (count($this->warnings) > 20) {
            $this->warn('Additional warnings: '.(count($this->warnings) - 20));
        }
    }
}
