<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Household extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * The users' belonging to this household
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
