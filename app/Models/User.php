<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    public const ROLE_CUSTOMER = 'customer';

    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_CATALOGUE_MANAGER = 'catalogue_manager';

    public const ROLE_CONTENT_EDITOR = 'content_editor';

    public const ROLE_ORDER_MANAGER = 'order_manager';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'mfa_secret' => 'encrypted',
            'mfa_enabled' => 'boolean',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'deletion_requested_at' => 'datetime',
        ];
    }

    public static function adminRoles(): array
    {
        return [
            self::ROLE_SUPER_ADMIN,
            self::ROLE_CATALOGUE_MANAGER,
            self::ROLE_CONTENT_EDITOR,
            self::ROLE_ORDER_MANAGER,
        ];
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, self::adminRoles(), true);
    }

    public function hasAbility(string $ability): bool
    {
        if (! $this->isAdmin() || ! $this->is_active) {
            return false;
        }

        if ($this->role === self::ROLE_SUPER_ADMIN) {
            return true;
        }

        return in_array($ability, config('permissions.roles.'.$this->role, []), true);
    }

    public function roleLabel(): string
    {
        return ucwords(str_replace('_', ' ', $this->role));
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function wishlistItems(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }
}
