<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PromoterFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company().' Boxing';

        return [
            'country_id' => Country::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'website_url' => fake()->url(),
        ];
    }
}
