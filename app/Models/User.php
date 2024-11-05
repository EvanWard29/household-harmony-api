<?php

namespace App\Models;

use App\Enums\AccountType;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

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
            'type' => AccountType::class,
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
     * Check if the user is an adult account
     */
    public function isAdult(): bool
    {
        return $this->type === AccountType::Adult;
    }

    /**
     * Check if the user is a child account
     */
    public function isChild(): bool
    {
        return $this->type === AccountType::Child;
    }
}
