<?php

namespace App\Models;

use App\Enums\RolesEnum;
use App\Services\UserService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

use function Illuminate\Events\queueable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * Defines a valid username
     */
    public const USERNAME_REGEX = '/(?!.*[\.\-\_]{2,})^[a-zA-Z0-9\.\-\_]{3,24}$/';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [
        'id',
        'household_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The household this user belongs to
     */
    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    /**
     * The task's the user has been assigned
     */
    public function tasks(): BelongsToMany
    {
        return $this->belongsToMany(Task::class);
    }

    /**
     * The user's reminder settings for tasks
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(UserReminder::class);
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(RolesEnum::ADMIN);
    }

    /**
     * Check if the user's household has a premium subscription
     */
    public function isSubscribed(): bool
    {
        return $this->household()->has('subscription')->exists();
    }

    /**
     * The `guard_name` to use for roles/permissions
     */
    public function guardName(): string
    {
        return config('auth.defaults.guard');
    }

    protected function getDefaultGuardName(): string
    {
        return config('auth.defaults.guard');
    }

    protected static function booted(): void
    {
        static::created(queueable(function (User $user) {
            if ($user->reminders()->doesntExist()) {
                // Create reminder settings for newly created users
                app(UserService::class, ['user' => $user])->createDefaultReminders();
            }
        }));
    }
}
