<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

if (! function_exists('currentUser')) {
    /**
     * Get the currently authenticated user.
     *
     * @return \App\Models\User|null
     */
    function currentUser()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return $user;
    }
}

if (! function_exists('mustCurrentUser')) {
    /**
     * Get the currently authenticated user or abort.
     *
     * @return \App\Models\User
     *
     * @throws HttpException
     */
    function mustCurrentUser()
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if ($user === null) {
            abort(401, 'Unauthenticated');
        }

        return $user;
    }
}
