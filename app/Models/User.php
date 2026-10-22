<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasLocalePreference
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * status, the suspension and deletion columns, last_login_at, email_verified_at and the phone columns are never mass-assigned:
     * they are changed only by server-side code.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'locale',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'phone',
        'phone_hash',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone' => 'encrypted',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'suspended_at' => 'datetime',
            'deletion_requested_at' => 'datetime',
            'deletion_scheduled_for' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    public function otpCodes(): HasMany
    {
        return $this->hasMany(OtpCode::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function biodata(): HasOne
    {
        return $this->hasOne(Biodata::class);
    }

    public function identityVerifications(): HasMany
    {
        return $this->hasMany(IdentityVerification::class);
    }

    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class);
    }

    /** Mail and notifications are written in the member's own language. */
    public function preferredLocale(): string
    {
        return array_key_exists((string) $this->locale, (array) config('lifepartner.locales'))
            ? (string) $this->locale
            : (string) config('lifepartner.default_locale');
    }

    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function markEmailAsVerified(): void
    {
        if (! $this->hasVerifiedEmail()) {
            $this->forceFill(['email_verified_at' => now()])->save();
        }
    }

    /** True only when a number is saved AND was confirmed with an SMS code. */
    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null && filled($this->phone);
    }

    /** True while the member's own request to delete the account is waiting out its waiting period. */
    public function hasDeletionRequested(): bool
    {
        return $this->deletion_requested_at !== null;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }
}
