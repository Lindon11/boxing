<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ranking extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'ranked_on' => 'date',
    ];

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(Organisation::class);
    }

    public function weightClass(): BelongsTo
    {
        return $this->belongsTo(WeightClass::class);
    }

    public function fighter(): BelongsTo
    {
        return $this->belongsTo(Fighter::class);
    }
}
