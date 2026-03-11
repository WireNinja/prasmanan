<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Actions\Auth;

use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class GetSocialiteRedirect
{
    /**
     * Get the redirect response for the given provider.
     */
    public function execute(string $provider, array $with = []): RedirectResponse
    {
        /** @var AbstractProvider $driver */
        $driver = Socialite::driver($provider);

        if (! empty($with)) {
            $driver->with($with);
        }

        return $driver->redirect();
    }
}
