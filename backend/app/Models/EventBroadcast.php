<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventBroadcast extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_ppv' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function broadcaster(): BelongsTo
    {
        return $this->belongsTo(Broadcaster::class);
    }
}
