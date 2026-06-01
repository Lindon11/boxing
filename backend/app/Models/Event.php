<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Event extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'event_date' => 'datetime',
        'ring_walks_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }

    public function promoter(): BelongsTo
    {
        return $this->belongsTo(Promoter::class);
    }

    public function fights(): HasMany
    {
        return $this->hasMany(Fight::class)->orderBy('bout_order');
    }

    public function broadcasts(): HasMany
    {
        return $this->hasMany(EventBroadcast::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->orderBy('sort_order');
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('status', 'upcoming')->orderBy('event_date');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed')->orderByDesc('event_date');
    }
}
