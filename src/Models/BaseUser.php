<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Models;

use App\Models\UserSocialAccount;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthentication;
use Filament\Auth\MultiFactor\App\Concerns\InteractsWithAppAuthenticationRecovery;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laragear\WebAuthn\Contracts\WebAuthnAuthenticatable;
use Laragear\WebAuthn\WebAuthnAuthentication;
use Laravel\Scout\Searchable;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Override;
use Spatie\Permission\Traits\HasRoles;
use WireNinja\Prasmanan\Concerns\MagicActivityLog;
use WireNinja\Prasmanan\Concerns\MagicGetterSetter;

/**
 * Base User Model for Prasmanan Core
 *
 * Handles all core traits logically without cluttering the main
 * application's User model. Applications should extend this model.
 */
abstract class BaseUser extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, WebAuthnAuthenticatable
{
    use HasPushSubscriptions;
    use HasRoles;
    use InteractsWithAppAuthentication;
    use InteractsWithAppAuthenticationRecovery;
    use MagicActivityLog;
    use MagicGetterSetter;
    use Notifiable;
    use Searchable;
    use WebAuthnAuthentication;

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'immutable_datetime',
            'password' => 'hashed',
            'is_suspended' => 'boolean',
            'suspended_until' => 'immutable_datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(config('filament-shield.super_admin.name', 'super_admin'));
    }

    /**
     * @return HasMany<BaseUserSocialAccount, $this>
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(config('prasmanan.models.social_account', UserSocialAccount::class));
    }

    /**
     * @return BelongsTo<static, $this>
     */
    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(static::class, 'suspended_by');
    }
}
