<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskReminder extends Model
{
    use MassPrunable;

    protected $guarded = ['id'];

    public $timestamps = false;

    /**
     * The task this reminder is for
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * The user this reminder is for
     */
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    /**
     * Get the prunable models
     */
    public function prunable(): Builder
    {
        return static::where('sent_at', '<', now()->subQuarter());
    }
}
