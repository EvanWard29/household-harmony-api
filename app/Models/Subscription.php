<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $guarded = ['id'];

    /**
     * The household this subscription belongs to
     */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}
