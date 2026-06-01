<?php

namespace Database\Factories;

use App\Models\Promoter;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EventFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->lastName().' Fight Night';

        return [
            'venue_id' => Venue::factory(),
            'promoter_id' => Promoter::factory(),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'name' => $name,
            'subtitle' => fake()->optional()->words(3, true),
            'event_date' => fake()->dateTimeBetween('+1 week', '+8 months'),
            'ring_walks_at' => fake()->dateTimeBetween('+1 week', '+8 months'),
            'status' => 'upcoming',
            'poster_url' => 'https://images.unsplash.com/photo-1517438322307-e67111335449?auto=format&fit=crop&w=900&q=80',
            'hero_image_url' => 'https://images.unsplash.com/photo-1549719386-74dfcbf7dbed?auto=format&fit=crop&w=1400&q=80',
            'broadcast_notes' => 'Global streaming details TBC',
        ];
    }
}
