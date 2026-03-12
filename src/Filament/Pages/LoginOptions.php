<?php

namespace WireNinja\Prasmanan\Filament\Pages;

use Exception;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Override;
use WireNinja\Prasmanan\Settings\SystemAuthSettings;

/**
 * @property-read Action $registerAction
 * @property-read Schema $form
 * @property-read Schema $multiFactorChallengeForm
 */
class LoginOptions extends Login
{
    protected static string $layout = 'prasmanan::components.filament-panels.layout.split';

    // 1. Tambahkan property public untuk menampung data dari JS
    public ?string $push_endpoint = null;

    public ?string $push_key = null;

    public ?string $push_token = null;

    public ?string $push_encoding = null;

    #[Override]
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                View::make('prasmanan::auth.web-push-overlay'),

                Section::make()
                    ->schema([
                        $this->getEmailFormComponent()
                            ->default(fn() => app()->isLocal() ? 'test@example.com' : null),
                        $this->getPasswordFormComponent()
                            ->default(fn() => app()->isLocal() ? 'password' : null),
                        $this->getRememberFormComponent(),
                    ])
                    ->visible(fn(SystemAuthSettings $settings) => $settings->allow_form_base_credential),

                $this->getOAuthSection(),
            ]);
    }

    protected function getOAuthSection(): Component
    {
        return Section::make()
            ->schema([
                Text::make('Atau lanjutkan dengan')
                    ->extraAttributes(['class' => 'text-center text-sm text-gray-500']),

                Action::make('google')
                    ->label('Lanjutkan dengan Google')
                    ->icon('heroicon-o-globe-alt')
                    ->color('gray')
                    ->outlined()
                    ->extraAttributes(['class' => 'w-full'])
                    ->visible(fn(SystemAuthSettings $settings) => $settings->allow_google_auth)
                    ->actionJs(function (): string {
                        $route = route('auth.google.redirect', ['provider' => 'google']);

                        return <<<JS
                                window.location.href = '$route';
                            JS;
                    }),

                Action::make('passkey')
                    ->label('Masuk dengan Passkey')
                    ->icon('heroicon-o-finger-print')
                    ->color('gray')
                    ->outlined()
                    ->extraAttributes(['class' => 'w-full'])
                    ->visible(fn(SystemAuthSettings $settings) => $settings->allow_webauth)
                    ->actionJs(
                        // Redirect to standard WebAuthn login route
                        // Ensure you have defined 'webauthn.login' or adjust path
                        fn(): string => <<<'JS'
                                window.location.href = '/webauthn/login';
                            JS
                    ),
            ])
            ->visible(fn(SystemAuthSettings $settings) => $settings->allow_google_auth || $settings->allow_webauth)
            ->extraAttributes(['class' => 'mt-6']);
    }

    public function initiatePasskeyLogin(): void
    {
        $this->dispatch('webauthn:login');
    }

    #[Override]
    public function getTitle(): string|Htmlable
    {
        return 'Masuk';
    }

    #[Override]
    public function getHeading(): string|Htmlable|null
    {
        return 'Masuk ke akun Anda';
    }

    #[Override]
    public function getSubheading(): string|Htmlable|null
    {
        if (filament()->hasRegistration()) {
            return new HtmlString(
                'Belum punya akun? ' .
                    '<a wire:navigate href="' . filament()->getRegistrationUrl() . '" class="text-primary-600 hover:underline">' .
                    'Daftar' .
                    '</a>'
            );
        }

        return null;
    }

    public function authenticate(): ?LoginResponse
    {
        // Jalankan autentikasi bawaan
        $result = parent::authenticate();

        if ($result === null) {
            return null;
        }

        $user = mustCurrentUser();

        // 3. Update Push Subscription jika datanya berhasil ditangkap dari browser
        if ($this->push_endpoint) {
            if (! $this->push_key || ! $this->push_token || ! $this->push_encoding) {
                rescue(fn() => throw new Exception('Push Subscription Failed'));
            }

            $user->updatePushSubscription(
                $this->push_endpoint,
                $this->push_key,
                $this->push_token,
                $this->push_encoding
            );
        }

        return $result;
    }
}
