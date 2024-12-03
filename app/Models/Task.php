<?php

namespace App\Models;

use App\Enums\TaskStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * @var array Default model attribute values
     */
    protected $attributes = [
        'status' => TaskStatusEnum::TODO,
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'datetime',
            'status' => TaskStatusEnum::class,
        ];
    }

    /**
     * The household this tasks belongs to
     */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /**
     * The user who created this task
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * The users this task is assigned to
     */
    public function assigned(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * The group/category this task belongs to
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    /**
     * The scheduled reminders for this task
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(TaskReminder::class);
    }
}
