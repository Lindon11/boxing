<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Fighter extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'active' => 'boolean',
        'birth_date' => 'date',
        'debut_date' => 'date',
    ];

    protected $appends = ['record'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function stance(): BelongsTo
    {
        return $this->belongsTo(Stance::class);
    }

    public function weightClass(): BelongsTo
    {
        return $this->belongsTo(WeightClass::class);
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(FighterAlias::class);
    }

    public function redCornerFights(): HasMany
    {
        return $this->hasMany(Fight::class, 'red_corner_fighter_id');
    }

    public function blueCornerFights(): HasMany
    {
        return $this->hasMany(Fight::class, 'blue_corner_fighter_id');
    }

    public function wonFights(): HasMany
    {
        return $this->hasMany(Fight::class, 'winner_fighter_id');
    }

    public function beltHistory(): HasMany
    {
        return $this->hasMany(BeltHistory::class);
    }

    public function rankings(): HasMany
    {
        return $this->hasMany(Ranking::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->orderBy('sort_order');
    }

    public function fights(): Builder
    {
        return Fight::query()
            ->where('red_corner_fighter_id', $this->id)
            ->orWhere('blue_corner_fighter_id', $this->id);
    }

    public function currentBelts(): HasMany
    {
        return $this->hasMany(BeltHistory::class)->where('status', 'current');
    }

    public function getRecordAttribute(): string
    {
        $record = "{$this->wins}-{$this->losses}-{$this->draws}";

        if ($this->no_contests > 0) {
            $record .= " ({$this->no_contests} NC)";
        }

        return $record;
    }
}
