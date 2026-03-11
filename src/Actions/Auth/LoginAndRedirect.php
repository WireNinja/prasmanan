<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Actions\Auth;

use Filament\Facades\Filament;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

final class LoginAndRedirect
{
    /**
     * Log in the user, regenerate the session, and redirect.
     */
    public function execute(Authenticatable $user): RedirectResponse
    {
        Auth::login($user, remember: true);

        Session::regenerate();

        return redirect()->intended(Filament::getUrl());
    }
}
