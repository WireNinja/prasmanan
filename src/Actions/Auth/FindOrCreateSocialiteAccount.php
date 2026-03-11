<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Actions\Auth;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use WireNinja\Prasmanan\Models\BaseUser;

final class FindOrCreateSocialiteAccount
{
    /**
     * Find or create a user and link their social account.
     */
    public function execute(SocialiteUserContract $socialiteUser, string $provider): Authenticatable
    {
        $userModelClass = config('auth.providers.users.model', BaseUser::class);

        /** @var Authenticatable|null $user */
        $user = $userModelClass::query()->whereHas('socialAccounts', function ($q) use ($provider, $socialiteUser) {
            $q->where('provider', $provider)
                ->where('provider_id', $socialiteUser->getId());
        })->first();

        if ($user) {
            // Update social account token if needed, or simply return user
            return $user;
        }

        /** @var Authenticatable|null $user */
        $user = $userModelClass::query()->where('email', $socialiteUser->getEmail())->first();

        if (! $user) {
            /** @var Authenticatable $user */
            $user = $userModelClass::query()->create([
                'name' => $socialiteUser->getName() ?? $socialiteUser->getNickname() ?? 'User',
                'email' => $socialiteUser->getEmail(),
                'email_verified_at' => now(),
                'avatar' => $socialiteUser->getAvatar(),
            ]);
        }

        // Create social account link
        $user->socialAccounts()->create([
            'provider' => $provider,
            'provider_id' => $socialiteUser->getId(),
            'provider_email' => $socialiteUser->getEmail(),
            'provider_avatar' => $socialiteUser->getAvatar(),
            'provider_token' => $socialiteUser->token,
            'provider_refresh_token' => $socialiteUser->refreshToken ?? null,
            'token_expires_at' => $socialiteUser->expiresIn ? now()->addSeconds((int) $socialiteUser->expiresIn) : null,
        ]);

        return $user;
    }
}
