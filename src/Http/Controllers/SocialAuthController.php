<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Throwable;
use WireNinja\Prasmanan\Actions\Auth\FindOrCreateSocialiteAccount;
use WireNinja\Prasmanan\Actions\Auth\GetSocialiteRedirect;
use WireNinja\Prasmanan\Actions\Auth\GetSocialiteUser;
use WireNinja\Prasmanan\Actions\Auth\LoginAndRedirect;

final class SocialAuthController extends Controller
{
    public function __construct(
        private readonly GetSocialiteRedirect $getSocialiteRedirect,
        private readonly GetSocialiteUser $getSocialiteUser,
        private readonly FindOrCreateSocialiteAccount $findOrCreateSocialiteAccount,
        private readonly LoginAndRedirect $loginAndRedirect,
    ) {}

    public function redirect(string $provider = 'google'): \Symfony\Component\HttpFoundation\RedirectResponse|RedirectResponse
    {
        $with = [];
        if ($provider === 'google') {
            $with['prompt'] = 'select_account';
        }

        return $this->getSocialiteRedirect->execute($provider, $with);
    }

    public function callback(string $provider = 'google'): RedirectResponse
    {
        try {
            $socialUser = $this->getSocialiteUser->execute($provider);

            $user = $this->findOrCreateSocialiteAccount->execute($socialUser, $provider);

            return $this->loginAndRedirect->execute($user);
        } catch (Throwable $e) {
            Log::error($e->getMessage());

            return redirect()->to(url('/admin/login'))
                ->with('error', 'Gagal masuk menggunakan '.ucfirst($provider).'. '.$e->getMessage());
        }
    }
}
