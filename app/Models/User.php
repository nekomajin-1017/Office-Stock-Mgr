<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_USER = 'user';

    public const ROLE_ADMIN = 'admin';

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isUser(): bool
    {
        return $this->role === self::ROLE_USER;
    }

    public function isLastActiveAdmin(): bool
    {
        if (! $this->isAdmin() || ! $this->is_active) {
            return false;
        }

        return ! static::query()
            ->whereKeyNot($this->getKey())
            ->where('role', self::ROLE_ADMIN)
            ->where('is_active', true)
            ->exists();
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'created_by');
    }

    public function confirmedPurchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'confirmed_by');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'created_by');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'created_by');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }
}
