<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Actions\Auth;

use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;

final class GetSocialiteUser
{
    /**
     * Get the social user from Socialite.
     */
    public function execute(string $provider): SocialiteUserContract
    {
        return Socialite::driver($provider)->user();
    }
}
