<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Fighter;
use App\Models\WeightClass;
use Illuminate\Database\Eloquent\Factories\Factory;

class FightFactory extends Factory
{
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'weight_class_id' => WeightClass::query()->inRandomOrder()->value('id'),
            'red_corner_fighter_id' => Fighter::factory(),
            'blue_corner_fighter_id' => Fighter::factory(),
            'title' => fake()->optional()->words(3, true),
            'billing' => fake()->randomElement(['main_event', 'co_main_event', 'undercard']),
            'bout_order' => fake()->numberBetween(1, 8),
            'scheduled_rounds' => fake()->randomElement([8, 10, 12]),
            'is_title_fight' => fake()->boolean(25),
            'status' => 'scheduled',
        ];
    }
}
