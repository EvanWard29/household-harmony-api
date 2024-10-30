<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HouseholdInvite extends Model
{
    protected $fillable = ['email', 'token'];

    protected $primaryKey = 'token';
    protected $keyType = 'string';
    public $incrementing = false;

    /**
     * The household this invite belongs to
     */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}
