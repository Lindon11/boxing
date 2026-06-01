<?php

namespace Database\Factories;

use App\Models\Fighter;
use App\Models\Organisation;
use App\Models\WeightClass;
use Illuminate\Database\Eloquent\Factories\Factory;

class RankingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'organisation_id' => Organisation::query()->inRandomOrder()->value('id'),
            'weight_class_id' => WeightClass::query()->inRandomOrder()->value('id'),
            'fighter_id' => Fighter::factory(),
            'rank' => fake()->numberBetween(1, 15),
            'points' => fake()->numberBetween(50, 1000),
            'ranked_on' => now()->toDateString(),
        ];
    }
}
