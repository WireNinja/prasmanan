<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use WireNinja\Prasmanan\Concerns\ConfiguresApplication;
use WireNinja\Prasmanan\Concerns\ConfiguresFilament;
use WireNinja\Prasmanan\Console\Commands\Enum\GenerateEnumAnnotations;
use WireNinja\Prasmanan\Console\Commands\Env\PrintCommand;
use WireNinja\Prasmanan\Console\Commands\Model\GenerateAnnotations;
use WireNinja\Prasmanan\Console\Commands\Pwa\InstallCommand;
use WireNinja\Prasmanan\Console\Commands\Scout\ScoutFlushAllCommand;
use WireNinja\Prasmanan\Console\Commands\Scout\ScoutImportAllCommand;
use WireNinja\Prasmanan\Console\Commands\Showcase\SendDummyBroadcast;
use WireNinja\Prasmanan\Console\Commands\System\FormatCommand;
use WireNinja\Prasmanan\Console\Commands\System\PrepareCommand;
use WireNinja\Prasmanan\Console\Commands\System\RefreshCommand;
use WireNinja\Prasmanan\Console\Commands\System\ShieldCommand;
use WireNinja\Prasmanan\Livewire\BetterSidebar;
use WireNinja\Prasmanan\Livewire\SideNotifications;

final class PrasmananServiceProvider extends ServiceProvider
{
    use ConfiguresApplication;
    use ConfiguresFilament;

    public function register(): void
    {
        $this->registerConfigurations();
        $this->registerCommands();
    }

    public function boot(): void
    {
        $this->bootPublishes();
        $this->bootRoutes();
        $this->bootViews();
        $this->bootLivewireComponents();
        $this->bootConfigurations();
    }

    private function registerConfigurations(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/prasmanan.php',
            'prasmanan',
        );

        $vendorConfigs = [
            'activitylog',
            'auth-security',
            'backup',
            'blade-icons',
            'broadcasting',
            'data',
            'filament-shield',
            'filament',
            'horizon',
            'laravel-pdf',
            'livewire',
            'permission',
            'reverb',
            'scout',
            'services',
            'settings',
            'webauthn',
            'webpush',
        ];

        foreach ($vendorConfigs as $configName) {
            if (file_exists($filePath = __DIR__."/../config/vendors/{$configName}.php")) {
                $config = $this->app->make('config');
                $config->set($configName, array_merge(
                    $config->get($configName, []),
                    require $filePath,
                ));
            }
        }
    }

    private function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                GenerateEnumAnnotations::class,
                GenerateAnnotations::class,
                ScoutFlushAllCommand::class,
                ScoutImportAllCommand::class,
                PrintCommand::class,
                SendDummyBroadcast::class,
                ShieldCommand::class,
                PrepareCommand::class,
                RefreshCommand::class,
                FormatCommand::class,
            ]);
        }
    }

    private function bootPublishes(): void
    {
        $this->publishes([
            __DIR__.'/../config/prasmanan.php' => config_path('prasmanan.php'),
        ], 'prasmanan-config');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../stubs/create_prasmanan_core_tables.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_prasmanan_core_tables.php'),
            ], 'prasmanan-migrations');
        }
    }

    private function bootRoutes(): void
    {
        Route::middleware('web')
            ->group(__DIR__.'/../routes/web.php');
    }

    private function bootViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'prasmanan');
    }

    private function bootLivewireComponents(): void
    {
        Livewire::component('prasmanan-side-notifications', SideNotifications::class);
        // Livewire::component('prasmanan-better-sidebar', BetterSidebar::class);
    }

    private function bootConfigurations(): void
    {
        $this->configureApplication();
        $this->configureFilament();
    }
}
