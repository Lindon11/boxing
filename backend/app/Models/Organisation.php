<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organisation extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function belts(): HasMany
    {
        return $this->hasMany(Belt::class);
    }

    public function rankings(): HasMany
    {
        return $this->hasMany(Ranking::class);
    }
}
