<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class WeightClassFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Heavyweight', 'Light Heavyweight', 'Super Middleweight', 'Welterweight', 'Bantamweight']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'limit_pounds' => $name === 'Heavyweight' ? null : fake()->numberBetween(118, 200),
            'limit_kg' => null,
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
