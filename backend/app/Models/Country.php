<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function fighters(): HasMany
    {
        return $this->hasMany(Fighter::class);
    }

    public function venues(): HasMany
    {
        return $this->hasMany(Venue::class);
    }
}
