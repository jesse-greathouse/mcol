<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory,
    Illuminate\Foundation\Auth\User as Authenticatable,
    Illuminate\Notifications\Notifiable;

use Laravel\Fortify\TwoFactorAuthenticatable,
    Laravel\Jetstream\HasProfilePhoto,
    Laravel\Sanctum\HasApiTokens;

/**
 * Class User
 *
 * The User model represents an authenticated user in the system.
 * It includes authentication, notifications, profile photo handling,
 * and two-factor authentication features.
 *
 * @package App\Models
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasProfilePhoto, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name', // User's name
        'email', // User's email address
        'password', // User's password
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password', // Hide password during serialization
        'remember_token', // Hide remember token
        'two_factor_recovery_codes', // Hide two-factor recovery codes
        'two_factor_secret', // Hide two-factor secret key
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url', // Append the user's profile photo URL
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime', // Cast the email verification date to datetime
            'password'          => 'hashed', // Ensure password is hashed
        ];
    }
}
