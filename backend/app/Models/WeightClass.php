<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeightClass extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function fighters(): HasMany
    {
        return $this->hasMany(Fighter::class);
    }

    public function fights(): HasMany
    {
        return $this->hasMany(Fight::class);
    }

    public function belts(): HasMany
    {
        return $this->hasMany(Belt::class);
    }
}
