<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FightOfficial extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function fight(): BelongsTo
    {
        return $this->belongsTo(Fight::class);
    }

    public function referee(): BelongsTo
    {
        return $this->belongsTo(Referee::class);
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(Judge::class);
    }
}
