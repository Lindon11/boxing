<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CountryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->country();

        return [
            'name' => $name,
            'code' => strtoupper(substr(Str::slug($name, ''), 0, 3)),
            'flag_emoji' => null,
        ];
    }
}
