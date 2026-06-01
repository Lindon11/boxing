<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Belt extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function weightClass(): BelongsTo
    {
        return $this->belongsTo(WeightClass::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(BeltHistory::class);
    }

    public function currentReign(): HasMany
    {
        return $this->hasMany(BeltHistory::class)->where('status', 'current');
    }
}
