<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fight extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'fight_date' => 'datetime',
        'is_title_fight' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function weightClass(): BelongsTo
    {
        return $this->belongsTo(WeightClass::class);
    }

    public function redCorner(): BelongsTo
    {
        return $this->belongsTo(Fighter::class, 'red_corner_fighter_id');
    }

    public function blueCorner(): BelongsTo
    {
        return $this->belongsTo(Fighter::class, 'blue_corner_fighter_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Fighter::class, 'winner_fighter_id');
    }

    public function resultMethod(): BelongsTo
    {
        return $this->belongsTo(ResultMethod::class);
    }

    public function officials(): HasMany
    {
        return $this->hasMany(FightOfficial::class);
    }

    public function scorecards(): HasMany
    {
        return $this->hasMany(Scorecard::class);
    }
}
