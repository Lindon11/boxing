<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stance extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function fighters(): HasMany
    {
        return $this->hasMany(Fighter::class);
    }
}
