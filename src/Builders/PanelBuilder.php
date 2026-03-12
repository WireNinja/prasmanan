<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Builders;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Closure;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\FontProviders\GoogleFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use WireNinja\Prasmanan\Filament\Pages\BetterEditProfile;
use WireNinja\Prasmanan\Filament\Pages\LoginOptions;
use WireNinja\Prasmanan\Livewire\BetterSidebar;
use WireNinja\Prasmanan\Models\BaseUser;

class PanelBuilder
{
    public function __construct(
        private readonly Panel $panel,
        private readonly string $name,
    ) {}

    public function setAsDefault(): PanelBuilder
    {
        $this->panel
            ->default();

        return $this;
    }

    public function setDefaultConfiguration(): PanelBuilder
    {
        $path = $this->getNamespaceIn(); // Contoh: /Sales/ atau /
        $ns = $this->getNamespaceFor();  // Contoh: \Sales\ atau \

        $darkMode = config('prasmanan.filament.dark_mode', false);
        $font = config('prasmanan.filament.font', 'IBM Plex Sans');
        $colors = config('prasmanan.filament.colors', ['primary' => Color::Zinc]);
        $profilePage = config('prasmanan.filament.profile_page', BetterEditProfile::class);
        $loginPage = config('prasmanan.filament.login_page', LoginOptions::class);
        $sidebarWidth = config('prasmanan.filament.sidebar_width', '350px');
        $sidebarCollapsibleOnDesktop = config('prasmanan.filament.sidebar_collapsible_on_desktop', true);
        $collapsibleNavigationGroups = config('prasmanan.filament.collapsible_navigation_groups', true);
        $spaMode = config('prasmanan.filament.spa_mode', true);
        $spaUrlExceptions = config('prasmanan.filament.spa_url_exceptions', ['*/auth/google*']);
        $pages = config('prasmanan.filament.pages', [Dashboard::class]);
        $widgets = config('prasmanan.filament.widgets', [AccountWidget::class, FilamentInfoWidget::class]);

        $this->panel
            ->id($this->name)
            ->path($this->name)
            ->viteTheme('resources/css/filament/'.$this->name.'/theme.css')
            ->login($loginPage)
            ->profile(page: $profilePage, isSimple: false)
            ->darkMode($darkMode)
            ->sidebarWidth($sidebarWidth)
            ->sidebarLivewireComponent(BetterSidebar::class)
            // ->topbarLivewireComponent(BetterTopbar::class)
            ->globalSearch(false)
            ->broadcasting(fn () => config('prasmanan.broadcasting.enabled', false))
            ->databaseTransactions()
            ->databaseNotifications()
            ->databaseNotificationsPolling(null)
            ->colors($colors)
            ->topbar(false)
            ->maxContentWidth(Width::Full)
            ->font($font, provider: GoogleFontProvider::class)
            ->discoverResources(
                in: app_path("Filament{$path}Resources"),
                for: "App\\Filament{$ns}Resources"
            )
            ->discoverPages(
                in: app_path("Filament{$path}Pages"),
                for: "App\\Filament{$ns}Pages"
            )
            ->discoverWidgets(
                in: app_path("Filament{$path}Widgets"),
                for: "App\\Filament{$ns}Widgets"
            )
            ->pages($pages)
            ->widgets($widgets)
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        if (config('prasmanan.pwa.enabled', true)) {
            $this->panel->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render('@include("prasmanan::partials/pwa")'),
            );
        }

        if (config('prasmanan.broadcasting.enabled', false)) {
            $this->panel->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Blade::render('@include("prasmanan::partials/broadcasting")'),
            );
        }

        if ($spaMode) {
            $this->panel->spa()->spaUrlExceptions($spaUrlExceptions);
        }

        if ($collapsibleNavigationGroups) {
            $this->panel->collapsibleNavigationGroups();
        }

        if ($sidebarCollapsibleOnDesktop) {
            $this->panel->sidebarCollapsibleOnDesktop();
        }

        return $this;
    }

    public function injectMultiFactorAuthentication(?Closure $isRequired = null): static
    {
        $recoveryCodeCount = config('prasmanan.filament.mfa.recovery_code_count', 10);
        $codeWindow = config('prasmanan.filament.mfa.code_window', 10);

        $this->panel->multiFactorAuthentication([
            AppAuthentication::make()
                ->recoverable()
                ->recoveryCodeCount($recoveryCodeCount)
                ->codeWindow($codeWindow),
        ], isRequired: $isRequired instanceof Closure ? $isRequired : fn (?BaseUser $user): bool => $user?->isSuperAdmin() ?? false);

        return $this;
    }

    public function injectShieldPlugin(): static
    {
        $this->panel->plugin(
            FilamentShieldPlugin::make()
                ->gridColumns([
                    'default' => 1,
                    'sm' => 2,
                    'lg' => 3,
                ])
                ->sectionColumnSpan(1)
                ->checkboxListColumns([
                    'default' => 1,
                    'sm' => 2,
                    'lg' => 4,
                ])
                ->resourceCheckboxListColumns([
                    'default' => 1,
                    'sm' => 2,
                ])
        );

        return $this;
    }

    public function build(): Panel
    {
        return $this->panel;
    }

    private function getNamespaceIn(): string
    {
        return strtolower($this->name) === 'admin'
            ? '/'
            : '/'.Str::studly($this->name).'/';
    }

    private function getNamespaceFor(): string
    {
        // Mengubah '/' menjadi '\' dari hasil getNamespaceIn()
        return str_replace('/', '\\', $this->getNamespaceIn());
    }
}
