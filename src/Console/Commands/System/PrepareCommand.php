<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Console\Commands\System;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use WireNinja\Prasmanan\Supports\PrasmananConstants;

final class PrepareCommand extends Command
{
    protected $signature = 'prasmanan:system-prepare 
                            {--force : Force overwrite existing configuration files}
                            {--production : Run checks for production readiness}
                            {--check : Only check for mandatory files without making changes}';

    protected $description = 'Run system preparation tasks, validate project environment, and sync opinionated configurations.';

    public function handle(): int
    {
        $isCheckOnly = $this->option('check') || $this->option('production');
        $isProduction = $this->option('production');

        if ($isProduction) {
            $this->components->info('Running Prasmanan Production Readiness Check...');
        } elseif ($isCheckOnly) {
            $this->components->info('Checking Prasmanan System Status...');
        } else {
            $this->components->info('Starting Prasmanan System Preparation...');
        }

        $this->checkMandatoryFiles($isCheckOnly);
        $this->syncAnalysisConfigs($isCheckOnly);
        $this->syncEnumStubs($isCheckOnly);
        $this->validatePackageJson($isCheckOnly);
        $this->validateEnvironment($isCheckOnly, $isProduction);
        $this->validateViteConfig($isCheckOnly);
        $this->validateRouting($isCheckOnly);
        $this->validateMigrations($isCheckOnly);
        $this->validateBootstrap($isCheckOnly);
        $this->validateLangDir($isCheckOnly);
        $this->validateSvgDir($isCheckOnly);
        $this->cleanupSchedules($isCheckOnly);

        if (! $isCheckOnly) {
            // Existing automated tasks
            $this->components->task('Running Shield setup...', fn() => $this->callSilent('prasmanan:system-shield') === 0);

            $this->components->task('Generating Model Annotations...', fn() => $this->callSilent('prasmanan:model-annotate', ['--all' => true]) === 0);

            $this->newLine();
            $this->components->info('✓ System preparation completed successfully!');
        } else {
            $this->newLine();
            $label = $isProduction ? 'Production readiness check' : 'System check';
            $this->components->info("✓ {$label} completed!");
        }

        return self::SUCCESS;
    }

    private function checkMandatoryFiles(bool $isCheckOnly): void
    {
        $files = [
            'pwa-assets.config.ts',
            'pwa-iconify-fetch.js',
            'pwa-icons-copy.js',
            'pwa-vite.config.ts',
            'resources/icons/lucide',
        ];

        foreach ($files as $file) {
            $exists = File::exists(base_path($file));
            if ($isCheckOnly) {
                $status = $exists ? '✓' : '✗';
                $this->line("  {$status} Mandatory path: {$file}");
            } elseif (! $exists) {
                $this->components->warn("Missing mandatory path: {$file}");
            }
        }
    }

    private function syncAnalysisConfigs(bool $isCheckOnly): void
    {
        $stubs = [
            'rector.php' => 'rector.php.stub',
            'phpstan.neon' => 'phpstan.neon.stub',
            'vite.config.js' => 'vite.config.js.stub',
            'resources/css/app.css' => 'css/app.css.stub',
            'resources/css/sources.css' => 'css/sources.css.stub',
            'resources/css/pdf.css' => 'css/pdf.css.stub',
        ];

        $this->syncStubs($stubs, $isCheckOnly, 'Config/Stub');
    }

    private function syncEnumStubs(bool $isCheckOnly): void
    {
        $stubs = [
            'app/Enums/PanelEnum.php' => 'PanelEnum.php.stub',
            'app/Enums/RoleEnum.php' => 'RoleEnum.php.stub',
            'app/Enums/FilamentResourceEnum.php' => 'FilamentResourceEnum.php.stub',
        ];

        if (! File::isDirectory(base_path('app/Enums'))) {
            if ($isCheckOnly) {
                $this->line('  ✗ Enum directory: app/Enums');
            } else {
                File::makeDirectory(base_path('app/Enums'), 0755, true);
            }
        }

        $this->syncStubs($stubs, $isCheckOnly, 'Enum');
    }

    private function syncStubs(array $stubs, bool $isCheckOnly, string $label): void
    {
        foreach ($stubs as $file => $stub) {
            $targetPath = base_path($file);
            $stubPath = PrasmananConstants::stubsDir() . "/{$stub}";

            $exists = File::exists($targetPath);

            if ($isCheckOnly) {
                $status = $exists ? '✓' : '✗';
                $this->line("  {$status} {$label}: {$file}");
                continue;
            }

            if (! $exists || $this->option('force')) {
                $this->components->task("Syncing {$file} from prasmanan stubs...", function () use ($stubPath, $targetPath) {
                    if (File::exists($stubPath)) {
                        return File::copy($stubPath, $targetPath);
                    }

                    return false;
                });
            }
        }
    }

    private function validatePackageJson(bool $isCheckOnly): void
    {
        if (! File::exists(base_path('package.json'))) {
            $this->components->error('package.json not found!');

            return;
        }

        $package = json_decode(File::get(base_path('package.json')), true);
        $scripts = $package['scripts'] ?? [];

        $mandatoryScripts = [
            'iconify:fetch',
            'pwa:assets',
            'pwa:icons',
            'pwa:iconify',
            'pwa:copy',
        ];

        if ($isCheckOnly) {
            $this->line('  Package scripts:');
        }

        foreach ($mandatoryScripts as $script) {
            $exists = isset($scripts[$script]);
            if ($isCheckOnly) {
                $status = $exists ? '✓' : '✗';
                $this->line("    {$status} {$script}");
            } elseif (! $exists) {
                $this->components->warn("Missing script in package.json: {$script}");
            }
        }
    }

    private function validateEnvironment(bool $isCheckOnly, bool $isProduction = false): void
    {
        $hasEnv = File::exists(base_path('.env'));

        if (! $hasEnv) {
            $this->components->error('.env file missing!');

            return;
        }

        $content = File::get(base_path('.env'));
        $lines = explode("\n", $content);
        $keys = [];
        $values = [];
        $duplicates = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                $segments = explode('=', $line, 2);
                $key = $segments[0];
                $value = trim($segments[1], '"\' ');

                if (isset($keys[$key])) {
                    $duplicates[] = $key;
                }
                $keys[$key] = true;
                $values[$key] = $value;
            }
        }

        if ($isCheckOnly && ! $isProduction) {
            if (! empty($duplicates)) {
                $this->line('  ✗ Duplicate .env keys: ' . implode(', ', array_unique($duplicates)));
            } else {
                $this->line('  ✓ No duplicate .env keys found.');
            }

            $this->line('  .env keys found:');
            foreach (array_keys($keys) as $key) {
                $this->line("    - {$key}");
            }
        } elseif (! empty($duplicates)) {
            $this->components->warn('Duplicate keys found in .env: ' . implode(', ', array_unique($duplicates)));
        }

        $crucialKeys = ['APP_KEY', 'DB_CONNECTION'];

        foreach ($crucialKeys as $key) {
            if (! isset($keys[$key])) {
                $this->components->warn("Crucial .env key might be missing: {$key}");
            }
        }

        // Locale & Logging check
        $locale = $values['APP_LOCALE'] ?? 'en';
        $logStack = $values['LOG_STACK'] ?? '';

        if ($isCheckOnly) {
            $statusLocale = ($locale === 'id') ? '✓' : '✗';
            $statusLog = str_contains($logStack, 'daily') ? '✓' : '✗';
            $this->line("  {$statusLocale} Locale: {$locale} (Expected: id)");
            $this->line("  {$statusLog} Log Stack: {$logStack} (Must contain: daily)");
        } else {
            if ($locale !== 'id') {
                $this->components->warn("APP_LOCALE is set to '{$locale}'. Recommended: 'id'.");
            }
            if (! str_contains($logStack, 'daily')) {
                $this->components->warn("LOG_STACK does not contain 'daily'. Current: '{$logStack}'.");
            }
        }

        // Production Specific Checks
        if ($isProduction) {
            $this->newLine();
            $this->line('  Production Readiness Details:');

            $env = $values['APP_ENV'] ?? 'local';
            $debug = strtolower($values['APP_DEBUG'] ?? 'true');
            $logLevel = strtolower($values['LOG_LEVEL'] ?? 'debug');

            $statusEnv = ($env !== 'local') ? '✓' : '✗';
            $statusDebug = ($debug === 'false') ? '✓' : '✗';
            $statusLevel = ($logLevel !== 'debug') ? '✓' : '✗';

            $this->line("    {$statusEnv} APP_ENV: {$env} (Must NOT be 'local')");
            $this->line("    {$statusDebug} APP_DEBUG: {$debug} (Must be 'false')");
            $this->line("    {$statusLevel} LOG_LEVEL: {$logLevel} (Must NOT be 'debug')");
        }
    }

    private function validateViteConfig(bool $isCheckOnly): void
    {
        $file = 'vite.config.js';
        $exists = File::exists(base_path($file));

        if ($isCheckOnly) {
            $status = $exists ? '✓' : '✗';
            $this->line("  {$status} Vite Config: {$file}");
        }

        if ($exists) {
            $content = File::get(base_path($file));

            $checks = [
                'vite-helpers' => 'Prasmanan Vite Helpers imported',
                'getViteInputs()' => 'Using getViteInputs() for input mapping',
                'commonWatchExclusions' => 'Using commonWatchExclusions for ignored paths',
                'VitePWA' => 'VitePWA plugin registered',
                'getPwaConfig' => 'Using getPwaConfig() for PWA configuration',
            ];

            foreach ($checks as $pattern => $label) {
                $found = str_contains($content, $pattern);
                if ($isCheckOnly) {
                    $status = $found ? '✓' : '✗';
                    $this->line("    {$status} {$label}");
                } elseif (! $found) {
                    $this->components->warn("Vite Config optimization missing: {$label}");
                }
            }
        }
    }

    private function validateRouting(bool $isCheckOnly): void
    {
        $file = 'routes/channels.php';
        $exists = File::exists(base_path($file));

        if ($isCheckOnly) {
            $status = $exists ? '✓' : '✗';
            $this->line("  {$status} Routing: {$file}");
        }

        if ($exists) {
            $content = File::get(base_path($file));
            $broadcastCheck = str_contains($content, 'PrasmananBroadcast::all()');

            if ($isCheckOnly) {
                $status = $broadcastCheck ? '✓' : '✗';
                $this->line("    {$status} PrasmananBroadcast::all() registered");
            } elseif (! $broadcastCheck) {
                $this->components->warn('PrasmananBroadcast::all() not found in routes/channels.php');
            }
        }
    }

    private function validateMigrations(bool $isCheckOnly): void
    {
        $migrationsPath = database_path('migrations');
        if (! File::isDirectory($migrationsPath)) {
            if ($isCheckOnly) {
                $this->line('  ✗ Migrations directory not found!');
            }

            return;
        }

        $files = File::files($migrationsPath);
        $exists = false;

        foreach ($files as $file) {
            if (str_contains($file->getFilename(), 'create_prasmanan_core_tables')) {
                $exists = true;

                break;
            }
        }

        if ($isCheckOnly) {
            $status = $exists ? '✓' : '✗';
            $this->line("  {$status} Core Migration: create_prasmanan_core_tables");
        } elseif (! $exists) {
            $this->components->warn('Missing core migration: create_prasmanan_core_tables');
        }
    }

    private function validateBootstrap(bool $isCheckOnly): void
    {
        $file = 'bootstrap/app.php';
        if (! File::exists(base_path($file))) {
            return;
        }

        $content = File::get(base_path($file));
        $checks = [
            'commands:' => 'withCommands / routes/console.php',
            'channels:' => 'withChannels / routes/channels.php',
            'PrasmananExceptions::dontReportForGuestUser' => 'Exception handling for guest users',
        ];

        if ($isCheckOnly) {
            $this->line("  ✓ Bootstrap configuration: {$file}");
        }

        foreach ($checks as $pattern => $label) {
            $found = str_contains($content, $pattern);
            if ($isCheckOnly) {
                $status = $found ? '✓' : '✗';
                $this->line("    {$status} {$label}");
            } elseif (! $found) {
                $this->components->warn("Missing bootstrap config: {$label}");
            }
        }
    }

    private function validateLangDir(bool $isCheckOnly): void
    {
        $dirs = ['lang', 'lang/en', 'lang/id'];
        foreach ($dirs as $dir) {
            $exists = File::isDirectory(base_path($dir));
            if ($isCheckOnly) {
                $status = $exists ? '✓' : '✗';
                $this->line("  {$status} Directory: {$dir}");
            } elseif (! $exists) {
                $this->components->warn("Missing directory: {$dir}");
            }
        }
    }

    private function validateSvgDir(bool $isCheckOnly): void
    {
        $path = 'resources/svg';
        $exists = File::isDirectory(base_path($path));
        if ($isCheckOnly) {
            $status = $exists ? '✓' : '✗';
            $this->line("  {$status} Directory: {$path}");
        } elseif (! $exists) {
            $this->components->warn("Missing directory: {$path}");
        }
    }

    private function cleanupSchedules(bool $isCheckOnly): void
    {
        $file = 'routes/console.php';
        if (! File::exists(base_path($file))) {
            return;
        }

        $content = File::get(base_path($file));
        $patterns = [
            "/Schedule::command\(['\"]backup:(run|clean)['\"]\).*?;/s",
            "/(?:\\\\?WireNinja\\\\Prasmanan\\\\Supports\\\\Config\\\\)?Backup(Database|Full|Assets)Schedule::make\(\)(?:->[^;]+)*;/s",
        ];

        $found = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $found = true;

                break;
            }
        }

        if ($isCheckOnly) {
            $status = $found ? '✗ (Needs cleanup)' : '✓ (Clean)';
            $this->line("  {$status} Schedules: {$file}");
        } elseif ($found) {
            $this->components->task('Cleaning up schedules in routes/console.php...', function () use ($file, $patterns, $content) {
                $newContent = preg_replace($patterns, '', $content);
                // Remove potential double newlines
                $newContent = preg_replace("/\n{3,}/", "\n\n", $newContent);

                return File::put(base_path($file), trim($newContent) . PHP_EOL) !== false;
            });
        }
    }
}
