<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class StanceFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement(['Orthodox', 'Southpaw', 'Switch']);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
