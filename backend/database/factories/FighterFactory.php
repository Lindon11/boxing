<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Stance;
use App\Models\WeightClass;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FighterFactory extends Factory
{
    public function definition(): array
    {
        $first = fake()->firstName();
        $last = fake()->lastName();
        $wins = fake()->numberBetween(8, 35);
        $losses = fake()->numberBetween(0, 4);
        $draws = fake()->numberBetween(0, 2);

        return [
            'country_id' => Country::factory(),
            'stance_id' => Stance::query()->inRandomOrder()->value('id'),
            'weight_class_id' => WeightClass::query()->inRandomOrder()->value('id'),
            'slug' => Str::slug("{$first} {$last}").'-'.fake()->unique()->numberBetween(100, 999),
            'first_name' => $first,
            'last_name' => $last,
            'display_name' => "{$first} {$last}",
            'ring_name' => fake()->optional()->words(2, true),
            'birth_date' => fake()->dateTimeBetween('-42 years', '-20 years')->format('Y-m-d'),
            'birth_place' => fake()->city(),
            'residence' => fake()->city(),
            'height_cm' => fake()->numberBetween(160, 205),
            'reach_cm' => fake()->numberBetween(165, 215),
            'wins' => $wins,
            'losses' => $losses,
            'draws' => $draws,
            'no_contests' => 0,
            'knockouts' => fake()->numberBetween(0, $wins),
            'debut_date' => fake()->dateTimeBetween('-15 years', '-2 years')->format('Y-m-d'),
            'active' => true,
            'photo_url' => 'https://images.unsplash.com/photo-1549719386-74dfcbf7dbed?auto=format&fit=crop&w=900&q=80',
            'bio' => fake()->paragraph(),
        ];
    }
}
