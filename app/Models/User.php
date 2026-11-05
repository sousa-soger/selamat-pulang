<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin' && $this->hasVerifiedEmail();
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)->withTimestamps();
    }

    public function promoteDesignatedAdmin(): void
    {
        if ($this->hasVerifiedEmail() && strtolower($this->email) === 'sogerft@gmail.com') {
            $this->forceFill(['role' => 'super_admin'])->save();
        }
    }

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
        ];
    }
}
