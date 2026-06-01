<?php

namespace Database\Seeders;

use App\Models\Belt;
use App\Models\BeltHistory;
use App\Models\Broadcaster;
use App\Models\Country;
use App\Models\Event;
use App\Models\EventBroadcast;
use App\Models\Fight;
use App\Models\Fighter;
use App\Models\FighterAlias;
use App\Models\Organisation;
use App\Models\Promoter;
use App\Models\Ranking;
use App\Models\ResultMethod;
use App\Models\Stance;
use App\Models\Venue;
use App\Models\WeightClass;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BoxingDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $countries = collect([
                ['name' => 'Ukraine', 'code' => 'UKR'],
                ['name' => 'United Kingdom', 'code' => 'GBR'],
                ['name' => 'United States', 'code' => 'USA'],
                ['name' => 'Russia', 'code' => 'RUS'],
                ['name' => 'Mexico', 'code' => 'MEX'],
                ['name' => 'Japan', 'code' => 'JPN'],
                ['name' => 'New Zealand', 'code' => 'NZL'],
                ['name' => 'China', 'code' => 'CHN'],
                ['name' => 'Australia', 'code' => 'AUS'],
                ['name' => 'Saudi Arabia', 'code' => 'SAU'],
            ])->mapWithKeys(fn (array $country) => [
                $country['code'] => Country::updateOrCreate(['code' => $country['code']], $country),
            ]);

            $stances = collect(['Orthodox', 'Southpaw', 'Switch'])->mapWithKeys(fn (string $name) => [
                $name => Stance::updateOrCreate(['slug' => str($name)->slug()->toString()], [
                    'name' => $name,
                    'slug' => str($name)->slug()->toString(),
                ]),
            ]);

            $weights = collect([
                ['name' => 'Heavyweight', 'slug' => 'heavyweight', 'limit_pounds' => null, 'limit_kg' => null, 'sort_order' => 1],
                ['name' => 'Light Heavyweight', 'slug' => 'light-heavyweight', 'limit_pounds' => 175, 'limit_kg' => 79.38, 'sort_order' => 2],
                ['name' => 'Super Middleweight', 'slug' => 'super-middleweight', 'limit_pounds' => 168, 'limit_kg' => 76.20, 'sort_order' => 3],
                ['name' => 'Welterweight', 'slug' => 'welterweight', 'limit_pounds' => 147, 'limit_kg' => 66.68, 'sort_order' => 4],
                ['name' => 'Lightweight', 'slug' => 'lightweight', 'limit_pounds' => 135, 'limit_kg' => 61.23, 'sort_order' => 5],
                ['name' => 'Super Bantamweight', 'slug' => 'super-bantamweight', 'limit_pounds' => 122, 'limit_kg' => 55.34, 'sort_order' => 6],
            ])->mapWithKeys(fn (array $weight) => [
                $weight['slug'] => WeightClass::updateOrCreate(['slug' => $weight['slug']], $weight),
            ]);

            $organisations = collect([
                ['name' => 'World Boxing Council', 'abbreviation' => 'WBC', 'slug' => 'wbc'],
                ['name' => 'World Boxing Association', 'abbreviation' => 'WBA', 'slug' => 'wba'],
                ['name' => 'International Boxing Federation', 'abbreviation' => 'IBF', 'slug' => 'ibf'],
                ['name' => 'World Boxing Organization', 'abbreviation' => 'WBO', 'slug' => 'wbo'],
                ['name' => 'The Ring', 'abbreviation' => 'RING', 'slug' => 'the-ring'],
            ])->mapWithKeys(fn (array $org) => [
                $org['abbreviation'] => Organisation::updateOrCreate(['slug' => $org['slug']], $org),
            ]);

            $promoters = collect([
                ['name' => 'Queensberry Promotions', 'slug' => 'queensberry-promotions', 'country_id' => $countries['GBR']->id, 'website_url' => 'https://www.queensberry.co.uk'],
                ['name' => 'Matchroom Boxing', 'slug' => 'matchroom-boxing', 'country_id' => $countries['GBR']->id, 'website_url' => 'https://www.matchroomboxing.com'],
                ['name' => 'Top Rank', 'slug' => 'top-rank', 'country_id' => $countries['USA']->id, 'website_url' => 'https://www.toprank.com'],
                ['name' => 'Premier Boxing Champions', 'slug' => 'premier-boxing-champions', 'country_id' => $countries['USA']->id, 'website_url' => 'https://www.premierboxingchampions.com'],
            ])->mapWithKeys(fn (array $promoter) => [
                $promoter['slug'] => Promoter::updateOrCreate(['slug' => $promoter['slug']], $promoter),
            ]);

            $venues = collect([
                ['name' => 'Kingdom Arena', 'slug' => 'kingdom-arena', 'city' => 'Riyadh', 'region' => null, 'country_id' => $countries['SAU']->id, 'capacity' => 26000],
                ['name' => 'The O2 Arena', 'slug' => 'the-o2-arena', 'city' => 'London', 'region' => 'England', 'country_id' => $countries['GBR']->id, 'capacity' => 20000],
                ['name' => 'Wembley Stadium', 'slug' => 'wembley-stadium', 'city' => 'London', 'region' => 'England', 'country_id' => $countries['GBR']->id, 'capacity' => 90000],
                ['name' => 'T-Mobile Arena', 'slug' => 't-mobile-arena', 'city' => 'Las Vegas', 'region' => 'Nevada', 'country_id' => $countries['USA']->id, 'capacity' => 20000],
            ])->mapWithKeys(fn (array $venue) => [
                $venue['slug'] => Venue::updateOrCreate(['slug' => $venue['slug']], $venue),
            ]);

            $methods = collect([
                ['name' => 'Knockout', 'abbreviation' => 'KO', 'slug' => 'ko'],
                ['name' => 'Technical Knockout', 'abbreviation' => 'TKO', 'slug' => 'tko'],
                ['name' => 'Unanimous Decision', 'abbreviation' => 'UD', 'slug' => 'ud'],
                ['name' => 'Majority Decision', 'abbreviation' => 'MD', 'slug' => 'md'],
                ['name' => 'Split Decision', 'abbreviation' => 'SD', 'slug' => 'sd'],
                ['name' => 'Draw', 'abbreviation' => 'DRAW', 'slug' => 'draw'],
            ])->mapWithKeys(fn (array $method) => [
                $method['abbreviation'] => ResultMethod::updateOrCreate(['slug' => $method['slug']], $method),
            ]);

            $fighters = collect($this->fighters($countries, $stances, $weights))->mapWithKeys(function (array $fighter) {
                $aliases = $fighter['aliases'] ?? [];
                unset($fighter['aliases']);

                $model = Fighter::updateOrCreate(['slug' => $fighter['slug']], $fighter);

                foreach ($aliases as $alias) {
                    FighterAlias::updateOrCreate([
                        'fighter_id' => $model->id,
                        'alias' => $alias,
                    ]);
                }

                return [$model->slug => $model];
            });

            $broadcasters = collect([
                ['name' => 'DAZN', 'slug' => 'dazn', 'country_id' => null, 'logo_url' => null, 'website_url' => 'https://www.dazn.com'],
                ['name' => 'TNT Sports Box Office', 'slug' => 'tnt-sports-box-office', 'country_id' => $countries['GBR']->id, 'logo_url' => null, 'website_url' => 'https://www.tntsports.co.uk'],
                ['name' => 'ESPN+ PPV', 'slug' => 'espn-plus-ppv', 'country_id' => $countries['USA']->id, 'logo_url' => null, 'website_url' => 'https://plus.espn.com'],
                ['name' => 'Sky Sports Box Office', 'slug' => 'sky-sports-box-office', 'country_id' => $countries['GBR']->id, 'logo_url' => null, 'website_url' => 'https://www.skysports.com/boxoffice'],
            ])->mapWithKeys(fn (array $broadcaster) => [
                $broadcaster['slug'] => Broadcaster::updateOrCreate(['slug' => $broadcaster['slug']], $broadcaster),
            ]);

            $events = collect([
                ['slug' => 'usyk-vs-fury-2', 'name' => 'Usyk vs Fury 2', 'subtitle' => 'Heavyweight Championship', 'event_date' => '2026-12-21 21:00:00', 'ring_walks_at' => '2026-12-21 23:00:00', 'status' => 'upcoming', 'venue_id' => $venues['kingdom-arena']->id, 'promoter_id' => $promoters['queensberry-promotions']->id, 'broadcast_notes' => 'DAZN PPV', 'ticket_url' => 'https://example.com/tickets/usyk-vs-fury-2'],
                ['slug' => 'beterbiev-vs-bivol-2', 'name' => 'Beterbiev vs Bivol 2', 'subtitle' => 'Undisputed Light Heavyweight Championship', 'event_date' => '2026-10-12 21:00:00', 'ring_walks_at' => '2026-10-12 23:00:00', 'status' => 'upcoming', 'venue_id' => $venues['kingdom-arena']->id, 'promoter_id' => $promoters['matchroom-boxing']->id, 'broadcast_notes' => 'DAZN PPV'],
                ['slug' => 'benn-vs-eubank-jr', 'name' => 'Benn vs Eubank Jr', 'subtitle' => 'Middleweight Grudge Match', 'event_date' => '2026-09-20 20:00:00', 'ring_walks_at' => '2026-09-20 22:30:00', 'status' => 'upcoming', 'venue_id' => $venues['the-o2-arena']->id, 'promoter_id' => $promoters['matchroom-boxing']->id, 'broadcast_notes' => 'DAZN'],
                ['slug' => 'joshua-vs-dubois', 'name' => 'Joshua vs Dubois', 'subtitle' => 'Heavyweight Championship', 'event_date' => '2025-09-21 20:00:00', 'ring_walks_at' => '2025-09-21 22:30:00', 'status' => 'completed', 'venue_id' => $venues['wembley-stadium']->id, 'promoter_id' => $promoters['queensberry-promotions']->id, 'broadcast_notes' => 'Sky Sports Box Office'],
                ['slug' => 'usyk-vs-fury-1', 'name' => 'Usyk vs Fury 1', 'subtitle' => 'Undisputed Heavyweight Championship', 'event_date' => '2025-05-18 21:00:00', 'ring_walks_at' => '2025-05-18 23:00:00', 'status' => 'completed', 'venue_id' => $venues['kingdom-arena']->id, 'promoter_id' => $promoters['queensberry-promotions']->id, 'broadcast_notes' => 'DAZN PPV'],
            ])->mapWithKeys(function (array $event) {
                $event += [
                    'poster_url' => 'https://images.unsplash.com/photo-1517438322307-e67111335449?auto=format&fit=crop&w=900&q=80',
                    'hero_image_url' => 'https://images.unsplash.com/photo-1549719386-74dfcbf7dbed?auto=format&fit=crop&w=1600&q=80',
                ];

                return [$event['slug'] => Event::updateOrCreate(['slug' => $event['slug']], $event)];
            });

            $this->syncBroadcasts($events, $broadcasters);
            $this->syncFights($events, $fighters, $weights, $methods);
            $this->syncBelts($organisations, $weights, $fighters, $events);
            $this->syncRankings($organisations, $weights, $fighters);
        });
    }

    private function fighters($countries, $stances, $weights): array
    {
        $photo = 'https://images.unsplash.com/photo-1549719386-74dfcbf7dbed?auto=format&fit=crop&w=900&q=80';

        return [
            ['slug' => 'oleksandr-usyk', 'first_name' => 'Oleksandr', 'last_name' => 'Usyk', 'display_name' => 'Oleksandr Usyk', 'ring_name' => 'The Cat', 'country_id' => $countries['UKR']->id, 'stance_id' => $stances['Southpaw']->id, 'weight_class_id' => $weights['heavyweight']->id, 'birth_date' => '1987-01-17', 'birth_place' => 'Simferopol, Ukraine', 'residence' => 'Kyiv, Ukraine', 'height_cm' => 191, 'reach_cm' => 198, 'wins' => 22, 'losses' => 0, 'draws' => 0, 'no_contests' => 0, 'knockouts' => 14, 'debut_date' => '2013-11-09', 'photo_url' => $photo, 'bio' => 'Oleksandr Usyk is an elite southpaw technician and undisputed heavyweight champion, known for movement, ring IQ, and championship-round control.', 'aliases' => ['Usyk', 'The Cat']],
            ['slug' => 'tyson-fury', 'first_name' => 'Tyson', 'last_name' => 'Fury', 'display_name' => 'Tyson Fury', 'ring_name' => 'The Gypsy King', 'country_id' => $countries['GBR']->id, 'stance_id' => $stances['Orthodox']->id, 'weight_class_id' => $weights['heavyweight']->id, 'birth_date' => '1988-08-12', 'birth_place' => 'Manchester, England', 'residence' => 'Morecambe, England', 'height_cm' => 206, 'reach_cm' => 216, 'wins' => 34, 'losses' => 1, 'draws' => 1, 'no_contests' => 0, 'knockouts' => 24, 'debut_date' => '2008-12-06', 'photo_url' => $photo, 'bio' => 'Tyson Fury is a towering former heavyweight champion with rare mobility, feints, and inside craft for the division.', 'aliases' => ['Fury', 'Gypsy King']],
            ['slug' => 'anthony-joshua', 'first_name' => 'Anthony', 'last_name' => 'Joshua', 'display_name' => 'Anthony Joshua', 'ring_name' => 'AJ', 'country_id' => $countries['GBR']->id, 'stance_id' => $stances['Orthodox']->id, 'weight_class_id' => $weights['heavyweight']->id, 'birth_date' => '1989-10-15', 'birth_place' => 'Watford, England', 'residence' => 'London, England', 'height_cm' => 198, 'reach_cm' => 208, 'wins' => 28, 'losses' => 4, 'draws' => 0, 'no_contests' => 0, 'knockouts' => 25, 'debut_date' => '2013-10-05', 'photo_url' => $photo, 'bio' => 'Anthony Joshua is an Olympic gold medallist and former unified heavyweight champion with elite finishing power.', 'aliases' => ['AJ']],
            ['slug' => 'daniel-dubois', 'first_name' => 'Daniel', 'last_name' => 'Dubois', 'display_name' => 'Daniel Dubois', 'ring_name' => 'Dynamite', 'country_id' => $countries['GBR']->id, 'stance_id' => $stances['Orthodox']->id, 'weight_class_id' => $weights['heavyweight']->id, 'birth_date' => '1997-09-06', 'birth_place' => 'London, England', 'residence' => 'London, England', 'height_cm' => 196, 'reach_cm' => 198, 'wins' => 22, 'losses' => 2, 'draws' => 0, 'no_contests' => 0, 'knockouts' => 21, 'debut_date' => '2017-04-08', 'photo_url' => $photo, 'bio' => 'Daniel Dubois is a heavy-handed British heavyweight with a direct style and fight-ending power.', 'aliases' => ['Dynamite']],
            ['slug' => 'artur-beterbiev', 'first_name' => 'Artur', 'last_name' => 'Beterbiev', 'display_name' => 'Artur Beterbiev', 'ring_name' => null, 'country_id' => $countries['RUS']->id, 'stance_id' => $stances['Orthodox']->id, 'weight_class_id' => $weights['light-heavyweight']->id, 'birth_date' => '1985-01-21', 'birth_place' => 'Khasavyurt, Russia', 'residence' => 'Montreal, Canada', 'height_cm' => 180, 'reach_cm' => 185, 'wins' => 21, 'losses' => 0, 'draws' => 0, 'no_contests' => 0, 'knockouts' => 20, 'debut_date' => '2013-06-08', 'photo_url' => $photo, 'bio' => "Artur Beterbiev is a pressure fighter with punishing power and one of boxing's most feared finishing rates.", 'aliases' => ['Beterbiev']],
            ['slug' => 'dmitry-bivol', 'first_name' => 'Dmitry', 'last_name' => 'Bivol', 'display_name' => 'Dmitry Bivol', 'ring_name' => null, 'country_id' => $countries['RUS']->id, 'stance_id' => $stances['Orthodox']->id, 'weight_class_id' => $weights['light-heavyweight']->id, 'birth_date' => '1990-12-18', 'birth_place' => 'Tokmak, Kyrgyzstan', 'residence' => 'Saint Petersburg, Russia', 'height_cm' => 183, 'reach_cm' => 183, 'wins' => 23, 'losses' => 1, 'draws' => 0, 'no_contests' => 0, 'knockouts' => 12, 'debut_date' => '2014-11-28', 'photo_url' => $photo, 'bio' => 'Dmitry Bivol is a disciplined light heavyweight boxer with elite distance control and clean combination punching.', 'aliases' => ['Bivol']],
            ['slug' => 'canelo-alvarez', 'first_name' => 'Canelo', 'last_name' => 'Alvarez', 'display_name' => 'Canelo Alvarez', 'ring_name' => 'Canelo', 'country_id' => $countries['MEX']->id, 'stance_id' => $stances['Orthodox']->id, 'weight_class_id' => $weights['super-middleweight']->id, 'birth_date' => '1990-07-18', 'birth_place' => 'Guadalajara, Mexico', 'residence' => 'Guadalajara, Mexico', 'height_cm' => 173, 'reach_cm' => 179, 'wins' => 62, 'losses' => 2, 'draws' => 2, 'no_contests' => 0, 'knockouts' => 39, 'debut_date' => '2005-10-29', 'photo_url' => $photo, 'bio' => 'Canelo Alvarez is a multi-weight champion known for counterpunching, body work, and elite big-fight experience.', 'aliases' => ['Canelo']],
            ['slug' => 'terence-crawford', 'first_name' => 'Terence', 'last_name' => 'Crawford', 'display_name' => 'Terence Crawford', 'ring_name' => 'Bud', 'country_id' => $countries['USA']->id, 'stance_id' => $stances['Switch']->id, 'weight_class_id' => $weights['welterweight']->id, 'birth_date' => '1987-09-28', 'birth_place' => 'Omaha, Nebraska', 'residence' => 'Omaha, Nebraska', 'height_cm' => 173, 'reach_cm' => 188, 'wins' => 41, 'losses' => 0, 'draws' => 0, 'no_contests' => 0, 'knockouts' => 31, 'debut_date' => '2008-03-14', 'photo_url' => $photo, 'bio' => 'Terence Crawford is a switch-hitting pound-for-pound star with timing, adaptability, and clinical finishing instincts.', 'aliases' => ['Bud', 'Crawford']],
            ['slug' => 'naoya-inoue', 'first_name' => 'Naoya', 'last_name' => 'Inoue', 'display_name' => 'Naoya Inoue', 'ring_name' => 'The Monster', 'country_id' => $countries['JPN']->id, 'stance_id' => $stances['Orthodox']->id, 'weight_class_id' => $weights['super-bantamweight']->id, 'birth_date' => '1993-04-10', 'birth_place' => 'Zama, Japan', 'residence' => 'Yokohama, Japan', 'height_cm' => 165, 'reach_cm' => 171, 'wins' => 28, 'losses' => 0, 'draws' => 0, 'no_contests' => 0, 'knockouts' => 25, 'debut_date' => '2012-10-02', 'photo_url' => $photo, 'bio' => 'Naoya Inoue is a destructive lower-weight champion with speed, precision, and concussive power.', 'aliases' => ['The Monster']],
            ['slug' => 'gervonta-davis', 'first_name' => 'Gervonta', 'last_name' => 'Davis', 'display_name' => 'Gervonta Davis', 'ring_name' => 'Tank', 'country_id' => $countries['USA']->id, 'stance_id' => $stances['Southpaw']->id, 'weight_class_id' => $weights['lightweight']->id, 'birth_date' => '1994-11-07', 'birth_place' => 'Baltimore, Maryland', 'residence' => 'Baltimore, Maryland', 'height_cm' => 166, 'reach_cm' => 171, 'wins' => 30, 'losses' => 0, 'draws' => 1, 'no_contests' => 0, 'knockouts' => 28, 'debut_date' => '2013-02-22', 'photo_url' => $photo, 'bio' => 'Gervonta Davis is a compact southpaw puncher with explosive counters and elite knockout instincts.', 'aliases' => ['Tank']],
            ['slug' => 'joseph-parker', 'first_name' => 'Joseph', 'last_name' => 'Parker', 'display_name' => 'Joseph Parker', 'ring_name' => null, 'country_id' => $countries['NZL']->id, 'stance_id' => $stances['Orthodox']->id, 'weight_class_id' => $weights['heavyweight']->id, 'birth_date' => '1992-01-09', 'birth_place' => 'Auckland, New Zealand', 'residence' => 'Auckland, New Zealand', 'height_cm' => 193, 'reach_cm' => 193, 'wins' => 35, 'losses' => 3, 'draws' => 0, 'no_contests' => 0, 'knockouts' => 23, 'debut_date' => '2012-07-05', 'photo_url' => $photo, 'bio' => 'Joseph Parker is a durable heavyweight contender with fast hands and deep championship experience.'],
            ['slug' => 'zhilei-zhang', 'first_name' => 'Zhilei', 'last_name' => 'Zhang', 'display_name' => 'Zhilei Zhang', 'ring_name' => 'Big Bang', 'country_id' => $countries['CHN']->id, 'stance_id' => $stances['Southpaw']->id, 'weight_class_id' => $weights['heavyweight']->id, 'birth_date' => '1983-05-02', 'birth_place' => 'Zhoukou, China', 'residence' => 'Bloomfield, New Jersey', 'height_cm' => 198, 'reach_cm' => 203, 'wins' => 27, 'losses' => 3, 'draws' => 1, 'no_contests' => 0, 'knockouts' => 22, 'debut_date' => '2014-08-08', 'photo_url' => $photo, 'bio' => 'Zhilei Zhang is a powerful southpaw heavyweight with patient pressure and sharp counter left hands.'],
            ['slug' => 'conor-benn', 'first_name' => 'Conor', 'last_name' => 'Benn', 'display_name' => 'Conor Benn', 'ring_name' => 'The Destroyer', 'country_id' => $countries['GBR']->id, 'stance_id' => $stances['Orthodox']->id, 'weight_class_id' => $weights['welterweight']->id, 'birth_date' => '1996-09-28', 'birth_place' => 'London, England', 'residence' => 'Essex, England', 'height_cm' => 173, 'reach_cm' => 173, 'wins' => 23, 'losses' => 0, 'draws' => 0, 'no_contests' => 0, 'knockouts' => 14, 'debut_date' => '2016-04-09', 'photo_url' => $photo, 'bio' => 'Conor Benn is an aggressive British contender built around fast starts, pressure, and combination punching.'],
            ['slug' => 'chris-eubank-jr', 'first_name' => 'Chris', 'last_name' => 'Eubank Jr', 'display_name' => 'Chris Eubank Jr', 'ring_name' => null, 'country_id' => $countries['GBR']->id, 'stance_id' => $stances['Orthodox']->id, 'weight_class_id' => $weights['super-middleweight']->id, 'birth_date' => '1989-09-18', 'birth_place' => 'Brighton, England', 'residence' => 'Brighton, England', 'height_cm' => 180, 'reach_cm' => 184, 'wins' => 33, 'losses' => 3, 'draws' => 0, 'no_contests' => 0, 'knockouts' => 24, 'debut_date' => '2011-11-12', 'photo_url' => $photo, 'bio' => 'Chris Eubank Jr is a seasoned contender with volume, athleticism, and a famous fighting lineage.'],
        ];
    }

    private function syncBroadcasts($events, $broadcasters): void
    {
        $rows = [
            ['event' => 'usyk-vs-fury-2', 'broadcaster' => 'dazn', 'region' => 'Global', 'platform' => 'DAZN PPV', 'is_ppv' => true, 'details' => 'Available in all DAZN markets'],
            ['event' => 'usyk-vs-fury-2', 'broadcaster' => 'tnt-sports-box-office', 'region' => 'United Kingdom', 'platform' => 'TV PPV', 'is_ppv' => true, 'details' => 'UK broadcast partner'],
            ['event' => 'beterbiev-vs-bivol-2', 'broadcaster' => 'dazn', 'region' => 'Global', 'platform' => 'DAZN PPV', 'is_ppv' => true, 'details' => 'International stream'],
            ['event' => 'benn-vs-eubank-jr', 'broadcaster' => 'dazn', 'region' => 'Global', 'platform' => 'DAZN', 'is_ppv' => false, 'details' => 'Subscription stream'],
            ['event' => 'joshua-vs-dubois', 'broadcaster' => 'sky-sports-box-office', 'region' => 'United Kingdom', 'platform' => 'TV PPV', 'is_ppv' => true, 'details' => 'UK box office'],
        ];

        foreach ($rows as $row) {
            EventBroadcast::updateOrCreate([
                'event_id' => $events[$row['event']]->id,
                'broadcaster_id' => $broadcasters[$row['broadcaster']]->id,
                'region' => $row['region'],
            ], [
                'platform' => $row['platform'],
                'is_ppv' => $row['is_ppv'],
                'details' => $row['details'],
            ]);
        }
    }

    private function syncFights($events, $fighters, $weights, $methods): void
    {
        $rows = [
            ['event' => 'usyk-vs-fury-2', 'red' => 'oleksandr-usyk', 'blue' => 'tyson-fury', 'weight' => 'heavyweight', 'title' => 'WBA, WBC, WBO and IBF heavyweight titles', 'billing' => 'main_event', 'order' => 1, 'rounds' => 12, 'status' => 'scheduled', 'is_title' => true],
            ['event' => 'usyk-vs-fury-2', 'red' => 'daniel-dubois', 'blue' => 'joseph-parker', 'weight' => 'heavyweight', 'title' => 'Heavyweight contender bout', 'billing' => 'co_main_event', 'order' => 2, 'rounds' => 12, 'status' => 'scheduled', 'is_title' => false],
            ['event' => 'usyk-vs-fury-2', 'red' => 'zhilei-zhang', 'blue' => 'anthony-joshua', 'weight' => 'heavyweight', 'title' => null, 'billing' => 'undercard', 'order' => 3, 'rounds' => 10, 'status' => 'scheduled', 'is_title' => false],
            ['event' => 'beterbiev-vs-bivol-2', 'red' => 'artur-beterbiev', 'blue' => 'dmitry-bivol', 'weight' => 'light-heavyweight', 'title' => 'Undisputed light heavyweight titles', 'billing' => 'main_event', 'order' => 1, 'rounds' => 12, 'status' => 'scheduled', 'is_title' => true],
            ['event' => 'benn-vs-eubank-jr', 'red' => 'conor-benn', 'blue' => 'chris-eubank-jr', 'weight' => 'super-middleweight', 'title' => 'Catchweight main event', 'billing' => 'main_event', 'order' => 1, 'rounds' => 12, 'status' => 'scheduled', 'is_title' => false],
            ['event' => 'joshua-vs-dubois', 'red' => 'daniel-dubois', 'blue' => 'anthony-joshua', 'weight' => 'heavyweight', 'title' => 'Heavyweight championship', 'billing' => 'main_event', 'order' => 1, 'rounds' => 12, 'status' => 'completed', 'is_title' => true, 'winner' => 'daniel-dubois', 'method' => 'KO', 'completed_rounds' => 5, 'notes' => 'Dubois wins by KO (R5, 0:59)'],
            ['event' => 'usyk-vs-fury-1', 'red' => 'oleksandr-usyk', 'blue' => 'tyson-fury', 'weight' => 'heavyweight', 'title' => 'Undisputed heavyweight titles', 'billing' => 'main_event', 'order' => 1, 'rounds' => 12, 'status' => 'completed', 'is_title' => true, 'winner' => 'oleksandr-usyk', 'method' => 'SD', 'completed_rounds' => 12, 'notes' => 'Usyk wins by split decision'],
        ];

        foreach ($rows as $row) {
            Fight::updateOrCreate([
                'event_id' => $events[$row['event']]->id,
                'red_corner_fighter_id' => $fighters[$row['red']]->id,
                'blue_corner_fighter_id' => $fighters[$row['blue']]->id,
            ], [
                'weight_class_id' => $weights[$row['weight']]->id,
                'winner_fighter_id' => isset($row['winner']) ? $fighters[$row['winner']]->id : null,
                'result_method_id' => isset($row['method']) ? $methods[$row['method']]->id : null,
                'title' => $row['title'],
                'billing' => $row['billing'],
                'bout_order' => $row['order'],
                'scheduled_rounds' => $row['rounds'],
                'completed_rounds' => $row['completed_rounds'] ?? null,
                'is_title_fight' => $row['is_title'],
                'status' => $row['status'],
                'fight_date' => $events[$row['event']]->event_date,
                'result_notes' => $row['notes'] ?? null,
            ]);
        }
    }

    private function syncBelts($organisations, $weights, $fighters, $events): void
    {
        foreach (['WBA', 'WBC', 'IBF', 'WBO'] as $abbr) {
            $belt = Belt::updateOrCreate([
                'organisation_id' => $organisations[$abbr]->id,
                'weight_class_id' => $weights['heavyweight']->id,
            ], [
                'name' => "{$abbr} Heavyweight World Title",
                'slug' => strtolower($abbr).'-heavyweight-world-title',
                'active' => true,
            ]);

            BeltHistory::updateOrCreate([
                'belt_id' => $belt->id,
                'fighter_id' => $fighters['oleksandr-usyk']->id,
                'status' => 'current',
            ], [
                'event_id' => $events['usyk-vs-fury-1']->id,
                'reign_started_on' => '2025-05-18',
                'reign_ended_on' => null,
                'result' => 'Won vs Tyson Fury',
            ]);
        }

        foreach (['WBC', 'IBF', 'WBO'] as $abbr) {
            $belt = Belt::updateOrCreate([
                'organisation_id' => $organisations[$abbr]->id,
                'weight_class_id' => $weights['light-heavyweight']->id,
            ], [
                'name' => "{$abbr} Light Heavyweight World Title",
                'slug' => strtolower($abbr).'-light-heavyweight-world-title',
                'active' => true,
            ]);

            BeltHistory::updateOrCreate([
                'belt_id' => $belt->id,
                'fighter_id' => $fighters['artur-beterbiev']->id,
                'status' => 'current',
            ], [
                'reign_started_on' => '2024-10-12',
                'reign_ended_on' => null,
                'result' => 'Unified champion',
            ]);
        }

        $featured = [
            ['org' => 'WBA', 'weight' => 'light-heavyweight', 'fighter' => 'dmitry-bivol'],
            ['org' => 'WBC', 'weight' => 'super-middleweight', 'fighter' => 'canelo-alvarez'],
            ['org' => 'WBO', 'weight' => 'welterweight', 'fighter' => 'terence-crawford'],
            ['org' => 'IBF', 'weight' => 'super-bantamweight', 'fighter' => 'naoya-inoue'],
            ['org' => 'WBA', 'weight' => 'lightweight', 'fighter' => 'gervonta-davis'],
        ];

        foreach ($featured as $row) {
            $belt = Belt::updateOrCreate([
                'organisation_id' => $organisations[$row['org']]->id,
                'weight_class_id' => $weights[$row['weight']]->id,
            ], [
                'name' => "{$row['org']} {$weights[$row['weight']]->name} World Title",
                'slug' => strtolower($row['org']).'-'.$row['weight'].'-world-title',
                'active' => true,
            ]);

            BeltHistory::updateOrCreate([
                'belt_id' => $belt->id,
                'fighter_id' => $fighters[$row['fighter']]->id,
                'status' => 'current',
            ], [
                'reign_started_on' => '2025-01-01',
                'reign_ended_on' => null,
                'result' => 'Current champion',
            ]);
        }
    }

    private function syncRankings($organisations, $weights, $fighters): void
    {
        $rows = [
            ['org' => 'WBA', 'weight' => 'heavyweight', 'fighter' => 'oleksandr-usyk', 'rank' => 1, 'points' => 1000],
            ['org' => 'WBA', 'weight' => 'heavyweight', 'fighter' => 'daniel-dubois', 'rank' => 2, 'points' => 750],
            ['org' => 'WBA', 'weight' => 'heavyweight', 'fighter' => 'joseph-parker', 'rank' => 3, 'points' => 587],
            ['org' => 'WBA', 'weight' => 'heavyweight', 'fighter' => 'zhilei-zhang', 'rank' => 4, 'points' => 455],
            ['org' => 'WBA', 'weight' => 'heavyweight', 'fighter' => 'tyson-fury', 'rank' => 5, 'points' => 430],
            ['org' => 'WBC', 'weight' => 'light-heavyweight', 'fighter' => 'artur-beterbiev', 'rank' => 1, 'points' => 1000],
            ['org' => 'WBC', 'weight' => 'light-heavyweight', 'fighter' => 'dmitry-bivol', 'rank' => 2, 'points' => 920],
            ['org' => 'RING', 'weight' => 'super-middleweight', 'fighter' => 'canelo-alvarez', 'rank' => 1, 'points' => 1000],
            ['org' => 'WBO', 'weight' => 'welterweight', 'fighter' => 'terence-crawford', 'rank' => 1, 'points' => 1000],
            ['org' => 'IBF', 'weight' => 'super-bantamweight', 'fighter' => 'naoya-inoue', 'rank' => 1, 'points' => 1000],
        ];

        foreach ($rows as $row) {
            Ranking::updateOrCreate([
                'organisation_id' => $organisations[$row['org']]->id,
                'weight_class_id' => $weights[$row['weight']]->id,
                'fighter_id' => $fighters[$row['fighter']]->id,
                'ranked_on' => '2026-06-01',
            ], [
                'rank' => $row['rank'],
                'points' => $row['points'],
            ]);
        }
    }
}
