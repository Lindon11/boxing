<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scorecard extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function fight(): BelongsTo
    {
        return $this->belongsTo(Fight::class);
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(Judge::class);
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Fighter::class, 'winner_fighter_id');
    }
}
