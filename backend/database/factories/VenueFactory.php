<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class VenueFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->city().' Arena';

        return [
            'country_id' => Country::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'city' => fake()->city(),
            'region' => fake()->optional()->state(),
            'capacity' => fake()->numberBetween(5000, 90000),
        ];
    }
}
