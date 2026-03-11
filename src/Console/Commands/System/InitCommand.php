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
        $this->setupPackageJson();
        $this->setupPwaAssets();
        $this->setupVite();
        $this->setupMigrations();
        $this->setupBroadcasting();
        $this->setupBootstrap();
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

    private function setupLanguage(): void
    {
        $this->components->task('Setting up Indonesian language...', function () {
            $this->callSilent('lang:add', ['locales' => ['id']]);
            $this->callSilent('lang:update');
            return true;
        });
    }
}
