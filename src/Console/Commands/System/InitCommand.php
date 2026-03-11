<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Console\Commands\System;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use WireNinja\Prasmanan\Supports\PrasmananConstants;

final class InitCommand extends Command
{
    protected $signature = 'prasmanan:init {--force : Overwrite existing files without asking}';

    protected $description = 'Initialize fresh project with Prasmanan opinionated stack.';

    public function handle(): int
    {
        $this->components->info('Initializing Prasmanan Opinionated Stack...');

        $this->setupEnvironment();
        $this->setupReverbKeys();
        $this->setupVapidKeys();
        $this->setupEnvExample();
        $this->setupPackageJson();
        $this->setupConfigs();
        $this->setupEnums();
        $this->setupPwaAssets();
        $this->setupVite();
        $this->setupMigrations();
        $this->setupBroadcasting();
        $this->setupBootstrap();
        $this->setupSchedules();
        $this->setupStorageLink();
        $this->setupLanguage();

        $this->newLine();
        $this->components->info('✓ Prasmanan initialization completed!');
        $this->components->info('Next step: Run "php artisan prasmanan:system-prepare" to verify.');

        return self::SUCCESS;
    }

    private function setupEnvironment(): void
    {
        $envPath = base_path('.env');
        if (! File::exists($envPath)) {
            return;
        }

        $this->components->task('Updating .env (Locale & Logging)...', function () use ($envPath) {
            $content = File::get($envPath);
            $content = preg_replace('/^APP_LOCALE=en$/m', 'APP_LOCALE=id', $content);
            $content = preg_replace('/^LOG_STACK=single$/m', 'LOG_STACK=daily', $content);
            
            return File::put($envPath, $content) !== false;
        });
    }

    private function setupReverbKeys(): void
    {
        $envPath = base_path('.env');
        if (! File::exists($envPath)) {
            return;
        }

        $this->components->task('Generating Reverb keys...', function () use ($envPath) {
            $content = File::get($envPath);
            $modified = false;

            $replacements = [
                '/^REVERB_APP_ID=$/m' => 'REVERB_APP_ID=' . random_int(100000, 999999),
                '/^REVERB_APP_KEY=$/m' => 'REVERB_APP_KEY=' . bin2hex(random_bytes(10)),
                '/^REVERB_APP_SECRET=$/m' => 'REVERB_APP_SECRET=' . bin2hex(random_bytes(15)),
            ];

            foreach ($replacements as $pattern => $replacement) {
                if (preg_match($pattern, $content)) {
                    $content = preg_replace($pattern, $replacement, $content);
                    $modified = true;
                }
            }

            return $modified ? File::put($envPath, $content) !== false : true;
        });
    }

    private function setupVapidKeys(): void
    {
        $envPath = base_path('.env');
        if (! File::exists($envPath)) {
            return;
        }

        $content = File::get($envPath);
        if (str_contains($content, 'VAPID_PUBLIC_KEY=') && ! empty(trim(explode('=', explode("\n", substr($content, strpos($content, 'VAPID_PUBLIC_KEY=')))[0])[1] ?? ''))) {
            // Check if it's really set (not just the key name)
            preg_match('/^VAPID_PUBLIC_KEY=(.+)$/m', $content, $matches);
            if (! empty($matches[1])) {
                return;
            }
        }

        $this->components->task('Generating VAPID keys...', function () {
            return $this->callSilent('webpush:vapid') === 0;
        });
    }

    private function setupEnvExample(): void
    {
        $this->components->task('Syncing .env.example...', function () {
            return $this->callSilent('prasmanan:env-sync', ['--force' => true]) === 0;
        });
    }

    private function setupPackageJson(): void
    {
        $path = base_path('package.json');
        if (! File::exists($path)) {
            return;
        }

        $this->components->task('Registering PWA scripts in package.json...', function () use ($path) {
            $package = json_decode(File::get($path), true);
            if (! is_array($package)) {
                return false;
            }

            $scripts = [
                'iconify:fetch' => 'bun pwa-iconify-fetch.js',
                'pwa:assets' => 'bun pwa-assets-generator --preset minimal public/favicon.svg',
                'pwa:icons' => 'bun run pwa:iconify && bun run pwa:copy',
                'pwa:iconify' => 'bun pwa-iconify-fetch.js',
                'pwa:copy' => 'bun pwa-icons-copy.js'
            ];

            foreach ($scripts as $key => $val) {
                $package['scripts'][$key] = $val;
            }

            return File::put($path, json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) !== false;
        });
    }

    private function setupConfigs(): void
    {
        $stubs = [
            'rector.php' => 'rector.php.stub',
            'phpstan.neon' => 'phpstan.neon.stub',
            'resources/css/app.css' => 'css/app.css.stub',
            'resources/css/sources.css' => 'css/sources.css.stub',
            'resources/css/pdf.css' => 'css/pdf.css.stub',
        ];

        foreach ($stubs as $file => $stub) {
            $dest = base_path($file);
            if (! File::exists($dest) || $this->option('force')) {
                File::ensureDirectoryExists(dirname($dest));
                File::copy(PrasmananConstants::stubsDir() . '/' . $stub, $dest);
                $this->line("  <fg=green>Created</> {$file}");
            }
        }
    }

    private function setupEnums(): void
    {
        $enums = [
            'app/Enums/PanelEnum.php' => 'PanelEnum.php.stub',
            'app/Enums/RoleEnum.php' => 'RoleEnum.php.stub',
            'app/Enums/FilamentResourceEnum.php' => 'FilamentResourceEnum.php.stub',
        ];

        foreach ($enums as $file => $stub) {
            $dest = base_path($file);
            if (! File::exists($dest) || $this->option('force')) {
                File::ensureDirectoryExists(dirname($dest));
                File::copy(PrasmananConstants::stubsDir() . '/' . $stub, $dest);
                $this->line("  <fg=green>Created</> {$file}");
            }
        }
    }

    private function setupPwaAssets(): void
    {
        $pwaAssets = [
            'pwa-assets.config.stub' => 'pwa-assets.config.ts',
            'pwa-iconify-fetch.js.stub' => 'pwa-iconify-fetch.js',
            'pwa-icons-copy.js.stub' => 'pwa-icons-copy.js',
            'pwa-vite.config.stub' => 'pwa-vite.config.ts'
        ];

        foreach ($pwaAssets as $stub => $dest) {
            $src = PrasmananConstants::stubsDir() . '/pwa/' . $stub;
            $dsc = base_path($dest);
            
            if (File::exists($src) && (! File::exists($dsc) || $this->option('force'))) {
                File::copy($src, $dsc);
                $this->line("  <fg=green>Created</> {$dest}");
            }
        }
    }

    private function setupVite(): void
    {
        $vitePath = base_path('vite.config.js');
        $stubPath = PrasmananConstants::stubsDir() . '/vite.config.js.stub';

        if (! File::exists($vitePath)) {
            return;
        }

        $content = File::get($vitePath);
        if (str_contains($content, 'getViteInputs') && ! $this->option('force')) {
            return;
        }

        if ($this->option('force') || $this->confirm('Overwrite vite.config.js with Prasmanan optimized configuration?', true)) {
            $this->components->task('Syncing vite.config.js...', function () use ($stubPath, $vitePath) {
                return File::copy($stubPath, $vitePath);
            });
        }
    }

    private function setupMigrations(): void
    {
        $migrationsPath = database_path('migrations');
        $coreMigration = $migrationsPath . '/0000_00_00_000000_create_prasmanan_core_tables.php';

        if (File::exists($coreMigration) && ! $this->option('force')) {
            return;
        }

        if ($this->option('force') || $this->confirm('Replace default Laravel users migration with Prasmanan Core Tables?', true)) {
            $this->components->task('Setting up core migrations...', function () use ($migrationsPath, $coreMigration) {
                // Delete existing users migration
                $files = File::files($migrationsPath);
                foreach ($files as $file) {
                    if (str_contains($file->getFilename(), 'create_users_table')) {
                        File::delete($file->getPathname());
                    }
                }

                $stub = PrasmananConstants::stubsDir() . '/create_prasmanan_core_tables.php.stub';
                return File::copy($stub, $coreMigration);
            });
        }
    }

    private function setupBroadcasting(): void
    {
        $path = base_path('routes/channels.php');
        if (File::exists($path) && ! $this->option('force')) {
            return;
        }

        $this->components->task('Setting up routes/channels.php...', function () use ($path) {
            $content = "<?php\n\nuse Illuminate\Support\Facades\Broadcast;\nuse WireNinja\Prasmanan\Broadcasting\PrasmananBroadcast;\n\nPrasmananBroadcast::all();\n";
            return File::put($path, $content) !== false;
        });
    }

    private function setupBootstrap(): void
    {
        $path = base_path('bootstrap/app.php');
        if (! File::exists($path)) {
            return;
        }

        $this->components->task('Configuring bootstrap/app.php...', function () use ($path) {
            $content = File::get($path);
            
            // Channels
            if (! str_contains($content, 'withChannels') && ! str_contains($content, 'channels:')) {
                // Try to find console.php routes and append channels
                $content = preg_replace(
                    '/commands:\s*__DIR__\s*\.\s*\'\/..\/routes\/console\.php\',?/m',
                    "commands: __DIR__.'/../routes/console.php',\n        channels: __DIR__.'/../routes/channels.php',",
                    $content
                );
            }

            // Exceptions
            if (! str_contains($content, 'PrasmananExceptions')) {
                // Match withExceptions hook and inject logic
                $content = preg_replace(
                    '/(withExceptions\(function\s*\(Exceptions\s*\$exceptions\)(?:\s*:\s*void)?\s*\{(?:\s*\/\/)?)/m',
                    "$1\n        \\WireNinja\\Prasmanan\\Exceptions\\PrasmananExceptions::dontReportForGuestUser(\$exceptions);",
                    $content
                );
            }

            return File::put($path, $content) !== false;
        });
    }

    private function setupSchedules(): void
    {
        $path = base_path('routes/console.php');
        if (! File::exists($path)) {
            return;
        }

        $this->components->task('Setting up backup schedules in routes/console.php...', function () use ($path) {
            $content = File::get($path);

            $schedules = [
                'use WireNinja\Prasmanan\Console\Schedules\BackupAssetsSchedule;',
                'use WireNinja\Prasmanan\Console\Schedules\BackupDatabaseSchedule;',
                'use WireNinja\Prasmanan\Console\Schedules\CleanupBackupSchedule;',
                '',
                "BackupDatabaseSchedule::make()->dailyAt('02:00')->runInBackground();",
                "BackupAssetsSchedule::make()->dailyAt('02:00')->runInBackground();",
                "CleanupBackupSchedule::make()->daily()->runInBackground();",
            ];

            foreach ($schedules as $line) {
                if (! str_contains($content, $line)) {
                    $content = $this->injectScheduleLine($content, $line);
                }
            }

            return File::put($path, $content) !== false;
        });
    }

    private function injectScheduleLine(string $content, string $line): string
    {
        if (str_contains($line, 'use ')) {
            // Inject after the last use statement or after <?php
            if (preg_match_all('/^use\s+.*;/m', $content, $matches)) {
                $lastUse = end($matches[0]);
                return str_replace($lastUse, $lastUse . "\n" . $line, $content);
            }
            return str_replace("<?php\n", "<?php\n\n" . $line . "\n", $content);
        }

        // Inject before Artisan::command
        if (str_contains($content, 'Artisan::command')) {
            return str_replace('Artisan::command', $line . "\n\nArtisan::command", $content);
        }

        return $content . "\n" . $line . "\n";
    }

    private function setupStorageLink(): void
    {
        $this->components->task('Setting up storage link...', function () {
            $publicStoragePath = public_path('storage');

            if (File::exists($publicStoragePath) || is_link($publicStoragePath)) {
                @unlink($publicStoragePath);
            }

            return $this->callSilent('storage:link') === 0;
        });
    }

    private function setupLanguage(): void
    {
        $this->components->task('Setting up Indonesian language...', function () {
            $this->callSilent('lang:add', ['locales' => ['id']]);
            $this->callSilent('lang:update');
            return true;
        });
    }
}
