<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use WireNinja\Prasmanan\Models\BaseUser;

if (! function_exists('currentUser')) {
    /**
     * Get the currently authenticated user.
     *
     * Returns the authenticated User model or null if not authenticated.
     */
    function currentUser(): ?BaseUser
    {
        /** @var BaseUser|null $user */
        $user = Auth::user();

        return $user;
    }
}

if (! function_exists('mustCurrentUser')) {
    /**
     * Get the currently authenticated user or abort.
     *
     * Returns the authenticated User model or aborts with 401 if not authenticated.
     * Use this when you need a non-nullable User instance (e.g., for policy checks).
     *
     * @throws HttpException
     */
    function mustCurrentUser(): BaseUser
    {
        /** @var BaseUser|null $user */
        $user = Auth::user();

        if ($user === null) {
            abort(401, 'Unauthenticated');
        }

        return $user;
    }
}
