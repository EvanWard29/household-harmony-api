<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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

    /**
     * The user who created/owns the household
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }
}
