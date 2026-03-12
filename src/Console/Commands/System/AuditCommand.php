<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Console\Commands\System;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class AuditCommand extends Command
{
    protected $signature = 'prasmanan:audit {--production : Run checks for production readiness}';

    protected $description = 'Run a comprehensive audit of the Prasmanan system and project health.';

    private array $results = [];

    public function handle(): int
    {
        $isProduction = $this->option('production');

        $this->components->info($isProduction ? 'Running Prasmanan Production Readiness Audit...' : 'Running Prasmanan System Health Audit...');

        $this->auditFiles();
        $this->auditConfig();
        $this->auditEnums();
        $this->auditEnvironment($isProduction);
        $this->auditVite();
        $this->auditRouting();
        $this->auditDatabase();
        $this->auditBootstrap();
        $this->auditFolders();
        $this->auditSchedules();
        $this->auditPhpConfig();

        $this->renderTable();

        $hasFailures = collect($this->results)->contains('status', 'FAIL');

        if ($hasFailures) {
            $this->components->error('Audit found issues that need attention! Run "php artisan prasmanan:init" to fix missing files/configs.');

            return self::FAILURE;
        }

        $this->components->info('✓ Audit completed! Your project is in a healthy Prasmanan state.');

        return self::SUCCESS;
    }

    private function renderTable(): void
    {
        $rows = array_map(function ($row) {
            $status = match($row['status']) {
                'OK' => '<fg=green>✓ OK</>',
                'FAIL' => '<fg=red>✗ FAIL</>',
                'WARN' => '<fg=yellow>! WARN</>',
                default => $row['status'],
            };

            return [
                $row['category'],
                $row['component'],
                $status,
                $row['detail'],
            ];
        }, $this->results);

        $this->table(['Category', 'Component', 'Status', 'Detail / Action'], $rows);
    }

    private function addResult(string $category, string $component, bool $success, string $detail): void
    {
        $this->results[] = [
            'category' => $category,
            'component' => $component,
            'status' => $success ? 'OK' : 'FAIL',
            'detail' => $detail,
        ];
    }

    private function auditFiles(): void
    {
        $files = [
            'pwa-assets.config.ts' => 'PWA Config Assets',
            'pwa-iconify-fetch.js' => 'Iconify Fetcher',
            'pwa-icons-copy.js' => 'PWA Icons Copy',
            'pwa-vite-helpers.js' => 'Vite Config Helpers',
            'resources/js/sw.js' => 'PWA Service Worker',
            'app/Providers/Filament/AdminPanelProvider.php' => 'Admin Panel Provider',
            'resources/css/filament/admin/theme.css' => 'Admin Panel Theme',
            'resources/icons/lucide' => 'Lucide Icons Path',
            'public/pwa/icons/logo.png' => 'PWA Logo Icon',
        ];

        foreach ($files as $path => $label) {
            $exists = File::exists(base_path($path));
            $this->addResult('Files', $label, $exists, $exists ? 'Path exists' : 'Missing! Run init command');
        }
    }

    private function auditConfig(): void
    {
        $stubs = [
            'rector.php' => 'Rector Config',
            'phpstan.neon' => 'PHPStan Config',
            'vite.config.js' => 'Vite Config',
            'resources/css/app.css' => 'Main App CSS',
            'resources/css/sources.css' => 'Sources CSS',
            'resources/css/pdf.css' => 'PDF CSS',
        ];

        foreach ($stubs as $file => $label) {
            $exists = File::exists(base_path($file));
            $this->addResult('Config', $label, $exists, $exists ? 'File found' : 'Missing stub');
        }
    }

    private function auditEnums(): void
    {
        $enums = [
            'app/Enums/PanelEnum.php' => 'Panel Enum',
            'app/Enums/RoleEnum.php' => 'Role Enum',
            'app/Enums/FilamentResourceEnum.php' => 'Resource Enum',
        ];

        foreach ($enums as $file => $label) {
            $exists = File::exists(base_path($file));
            $this->addResult('Enums', $label, $exists, $exists ? 'File found' : 'Missing Enum');
        }
    }

    private function auditEnvironment(bool $isProduction): void
    {
        $envPath = base_path('.env');
        if (! File::exists($envPath)) {
            $this->addResult('Env', '.env File', false, 'Missing .env file!');

            return;
        }

        $content = File::get($envPath);

        // Locale
        preg_match('/^APP_LOCALE=(.*)$/m', $content, $matches);
        $locale = $matches[1] ?? 'en';
        $this->addResult('Env', 'Locale', $locale === 'id', "Current: {$locale} (Expect: id)");

        // Logging
        preg_match('/^LOG_STACK=(.*)$/m', $content, $matches);
        $logStack = $matches[1] ?? '';
        $this->addResult('Env', 'Log Stack', str_contains($logStack, 'daily'), str_contains($logStack, 'daily') ? 'Set to daily' : "Current: {$logStack} (Expect: daily)");

        if ($isProduction) {
            preg_match('/^APP_ENV=(.*)$/m', $content, $matches);
            $appEnv = $matches[1] ?? 'local';
            $this->addResult('Production', 'APP_ENV', $appEnv !== 'local', "Current: {$appEnv} (Must NOT be local)");

            preg_match('/^APP_DEBUG=(.*)$/m', $content, $matches);
            $debug = strtolower($matches[1] ?? 'true');
            $this->addResult('Production', 'APP_DEBUG', $debug === 'false', "Debug is {$debug}");
        }
    }

    private function auditVite(): void
    {
        $file = base_path('vite.config.js');
        if (! File::exists($file)) {
            $this->addResult('Vite', 'Config Integrity', false, 'File missing');

            return;
        }

        $content = File::get($file);
        $checks = [
            'getViteInputs' => 'Input Mapping',
            'commonWatchExclusions' => 'Watch Exclusions',
            'VitePWA' => 'PWA Plugin',
        ];

        foreach ($checks as $pattern => $label) {
            $found = str_contains($content, $pattern);
            $this->addResult('Vite', $label, $found, $found ? 'Optimized' : 'Fallback detected');
        }
    }

    private function auditRouting(): void
    {
        $file = base_path('routes/channels.php');
        $exists = File::exists($file);

        if ($exists) {
            $content = File::get($file);
            $found = str_contains($content, 'PrasmananBroadcast::all()');
            $this->addResult('Routing', 'Broadcast Registration', $found, $found ? 'Registered' : 'Missing PrasmananBroadcast::all()');
        } else {
            $this->addResult('Routing', 'Channels File', false, 'Missing routes/channels.php');
        }
    }

    private function auditDatabase(): void
    {
        $migrationsPath = database_path('migrations');
        $found = false;

        if (File::isDirectory($migrationsPath)) {
            $files = File::files($migrationsPath);
            foreach ($files as $file) {
                if (str_contains($file->getFilename(), 'create_prasmanan_core_tables')) {
                    $found = true;
                    break;
                }
            }
        }

        $this->addResult('Database', 'Core Migrations', $found, $found ? 'Schema found' : 'Missing 0000_..._core_tables');
    }

    private function auditBootstrap(): void
    {
        $file = base_path('bootstrap/app.php');
        if (! File::exists($file)) {
            $this->addResult('Bootstrap', 'App Blueprint', false, 'Missing bootstrap/app.php');

            return;
        }

        $content = File::get($file);
        $this->addResult('Bootstrap', 'Channels Route', str_contains($content, 'channels:'), str_contains($content, 'channels:') ? 'Injected' : 'Missing');
        $this->addResult('Bootstrap', 'Guest Exceptions', str_contains($content, 'PrasmananExceptions'), str_contains($content, 'PrasmananExceptions') ? 'Handled' : 'Unmanaged');
    }

    private function auditFolders(): void
    {
        $folders = [
            'lang/id' => 'Indonesian Lang',
            'resources/svg' => 'SVG Assets',
            'resources/icons/lucide' => 'Lucide Icons',
        ];

        foreach ($folders as $path => $label) {
            $exists = File::isDirectory(base_path($path));
            $this->addResult('Folders', $label, $exists, $exists ? 'Directory exists' : 'Missing folder');
        }
    }

    private function auditSchedules(): void
    {
        $file = base_path('routes/console.php');
        if (File::exists($file)) {
            $content = File::get($file);
            $hasLegacy = str_contains($content, 'backup:run') || str_contains($content, 'Automatically generated Backup Schedules');
            $this->addResult('Schedules', 'Legacy Cleanup', ! $hasLegacy, $hasLegacy ? 'Dirty! Contains legacy backup comments/commands' : 'Clean');
        }
    }

    private function auditPhpConfig(): void
    {
        $postMaxSize = ini_get('post_max_size');
        $uploadMaxFilesize = ini_get('upload_max_filesize');

        $isPostOk = $this->isSizeAtLeast($postMaxSize, 50);
        $isUploadOk = $this->isSizeAtLeast($uploadMaxFilesize, 50);

        $this->addResultDetailed('Server', 'PHP post_max_size', $isPostOk ? 'OK' : 'WARN', "Current: {$postMaxSize} (Recommended: 50M+)");
        $this->addResultDetailed('Server', 'PHP upload_max_filesize', $isUploadOk ? 'OK' : 'WARN', "Current: {$uploadMaxFilesize} (Recommended: 50M+)");
    }

    private function addResultDetailed(string $category, string $component, string $status, string $detail): void
    {
        $this->results[] = [
            'category' => $category,
            'component' => $component,
            'status' => $status,
            'detail' => $detail,
        ];
    }

    private function isSizeAtLeast(string $size, int $minMb): bool
    {
        $unit = strtoupper(substr($size, -1));
        $value = (int) substr($size, 0, -1);

        $bytes = match ($unit) {
            'G' => $value * 1024 * 1024 * 1024,
            'M' => $value * 1024 * 1024,
            'K' => $value * 1024,
            default => (int) $size,
        };

        return $bytes >= ($minMb * 1024 * 1024);
    }
}
