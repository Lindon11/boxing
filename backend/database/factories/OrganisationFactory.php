<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrganisationFactory extends Factory
{
    public function definition(): array
    {
        $abbreviation = fake()->unique()->randomElement(['WBC', 'WBA', 'IBF', 'WBO', 'RING']);

        return [
            'name' => $abbreviation === 'RING' ? 'The Ring' : $abbreviation,
            'abbreviation' => $abbreviation,
            'slug' => Str::slug($abbreviation),
        ];
    }
}
