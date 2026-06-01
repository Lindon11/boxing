<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BeltHistory extends Model
{
    use HasFactory;

    protected $table = 'belt_history';

    protected $guarded = [];

    protected $casts = [
        'reign_started_on' => 'date',
        'reign_ended_on' => 'date',
    ];

    public function belt(): BelongsTo
    {
        return $this->belongsTo(Belt::class);
    }

    public function fighter(): BelongsTo
    {
        return $this->belongsTo(Fighter::class);
    }

    public function fight(): BelongsTo
    {
        return $this->belongsTo(Fight::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
