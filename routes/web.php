<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Laragear\WebAuthn\Http\Routes as WebAuthnRoutes;
use WireNinja\Prasmanan\Http\Controllers\PwaManifestController;
use WireNinja\Prasmanan\Http\Controllers\SocialAuthController;
use WireNinja\Prasmanan\Http\Controllers\WebAuthn\WebAuthnLoginController as WebAuthnLogicLoginController;
use WireNinja\Prasmanan\Http\Controllers\WebAuthn\WebAuthnRedirectController;
use WireNinja\Prasmanan\Http\Controllers\WebAuthn\WebAuthnRegisterController as WebAuthnLogicRegisterController;

/*
|--------------------------------------------------------------------------
| Internal Routes for Prasmanan Library
|--------------------------------------------------------------------------
|
| These routes are mapped securely by the PrasmananServiceProvider to
| prevent RouteNotFoundException in the core Filament views (LoginOptions)
| and PWA integration.
|
*/

Route::get('/manifest.webmanifest', PwaManifestController::class)
    ->name('pwa.manifest');

Route::prefix('auth/{provider}')->group(function () {
    Route::get('redirect', [SocialAuthController::class, 'redirect'])
        ->name('auth.google.redirect'); // Named for legacy compat in LoginOptions

    Route::get('callback', [SocialAuthController::class, 'callback'])
        ->name('auth.social.callback');
});

Route::get('/webauthn/login', WebAuthnRedirectController::class)
    ->name('webauthn.login');

// Register all WebAuthn core routes (challenge, registration, login data)
WebAuthnRoutes::register(
    attestController: WebAuthnLogicRegisterController::class,
    assertController: WebAuthnLogicLoginController::class
)->withoutMiddleware(VerifyCsrfToken::class);
