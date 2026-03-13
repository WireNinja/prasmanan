<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use WireNinja\Prasmanan\Models\BaseUser;

if (! function_exists('currentUser')) {
    /**
     * Get the currently authenticated user.
     *
     * @return \App\Models\User|\WireNinja\Prasmanan\Models\BaseUser|null
     */
    function currentUser(): ?BaseUser
    {
        /** @var \App\Models\User|BaseUser|null $user */
        $user = Auth::user();

        return $user;
    }
}

if (! function_exists('mustCurrentUser')) {
    /**
     * Get the currently authenticated user or abort.
     *
     * @return \App\Models\User|\WireNinja\Prasmanan\Models\BaseUser
     *
     * @throws HttpException
     */
    function mustCurrentUser(): BaseUser
    {
        /** @var \App\Models\User|BaseUser|null $user */
        $user = Auth::user();

        if ($user === null) {
            abort(401, 'Unauthenticated');
        }

        return $user;
    }
}
