<?php

namespace WireNinja\Prasmanan\Supports;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

class PrasmananPlugin implements PluginInterface, EventSubscriberInterface
{
    public function activate(Composer $composer, IOInterface $io): void
    {
        // No action needed on activation
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // No action needed on deactivation
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // No action needed on uninstall
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::PRE_AUTOLOAD_DUMP => 'ensureDirectoriesExist',
            ScriptEvents::PRE_UPDATE_CMD => 'ensureDirectoriesExist',
            ScriptEvents::PRE_INSTALL_CMD => 'ensureDirectoriesExist',
            ScriptEvents::POST_UPDATE_CMD => 'initializeFreshProject',
            ScriptEvents::POST_INSTALL_CMD => 'initializeFreshProject',
            ScriptEvents::POST_CREATE_PROJECT_CMD => 'initializeFreshProject',
        ];
    }

    public static function ensureDirectoriesExist(Event $event): void
    {
        $directories = [
            'resources/svg',
            'resources/icons/lucide',
            'resources/css/filament',
            'lang/id',
        ];

        $baseDir = getcwd();

        foreach ($directories as $dir) {
            $path = $baseDir . DIRECTORY_SEPARATOR . $dir;

            if (! is_dir($path)) {
                if (@mkdir($path, 0755, true)) {
                    @file_put_contents($path . DIRECTORY_SEPARATOR . '.gitkeep', '');
                }
            }
        }
    }

    public static function initializeFreshProject(Event $event): void
    {
        $baseDir = getcwd();
        $storageDir = $baseDir . DIRECTORY_SEPARATOR . 'storage';
        if (! is_dir($storageDir)) {
            @mkdir($storageDir, 0755, true);
        }
        $lockFile = $storageDir . DIRECTORY_SEPARATOR . '.prasmanan_initialized';

        // Only run initialization once per project.
        if (file_exists($lockFile)) {
            return;
        }

        // 1. Copy PWA Assets
        // Avoid using __DIR__ directly in logic to prevent Composer pre-processing issues
        $pluginFile = (new \ReflectionClass(static::class))->getFileName();
        $pluginDir = dirname($pluginFile);
        $stubsDir = realpath($pluginDir . '/../../stubs');

        if ($stubsDir) {
            $pwaAssets = [
                'pwa-assets.config.stub' => 'pwa-assets.config.ts',
                'pwa-iconify-fetch.js.stub' => 'pwa-iconify-fetch.js',
                'pwa-icons-copy.js.stub' => 'pwa-icons-copy.js',
                'pwa-vite.config.stub' => 'pwa-vite.config.ts'
            ];

            foreach ($pwaAssets as $stub => $dest) {
                $stubPath = $stubsDir . '/pwa/' . $stub;
                $destPath = $baseDir . '/' . $dest;
                if (! file_exists($destPath) && file_exists($stubPath)) {
                    @copy($stubPath, $destPath);
                }
            }

            // Replace Vite Config
            $vitePath = $baseDir . '/vite.config.js';
            $viteStub = $stubsDir . '/vite.config.js.stub';
            if (file_exists($vitePath) && file_exists($viteStub)) {
                $currentVite = file_get_contents($vitePath);
                if (! str_contains($currentVite, 'getViteInputs')) {
                    @copy($viteStub, $vitePath);
                }
            }
        }

        // 2. Add Package.json Scripts
        $packageJsonPath = $baseDir . '/package.json';
        if (file_exists($packageJsonPath)) {
            $packageJson = json_decode(file_get_contents($packageJsonPath), true);
            if (is_array($packageJson)) {
                $updated = false;
                if (! isset($packageJson['scripts']['pwa:assets'])) {
                    $packageJson['scripts']['iconify:fetch'] = 'bun pwa-iconify-fetch.js';
                    $packageJson['scripts']['pwa:assets'] = 'bun pwa-assets-generator --preset minimal public/favicon.svg';
                    $packageJson['scripts']['pwa:icons'] = 'bun run pwa:iconify && bun run pwa:copy';
                    $packageJson['scripts']['pwa:iconify'] = 'bun pwa-iconify-fetch.js';
                    $packageJson['scripts']['pwa:copy'] = 'bun pwa-icons-copy.js';
                    $updated = true;
                }
                if ($updated) {
                    file_put_contents($packageJsonPath, json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                }
            }
        }

        // 3. Update .env values
        $envPath = $baseDir . '/.env';
        if (file_exists($envPath)) {
            $envCont = file_get_contents($envPath);
            $envCont = preg_replace('/^APP_LOCALE=.*$/m', 'APP_LOCALE=id', $envCont);
            $envCont = preg_replace('/^LOG_STACK=.*$/m', 'LOG_STACK=daily', $envCont);
            file_put_contents($envPath, $envCont);
        }

        // 5. Create routes/channels.php if missing
        $channelsPath = $baseDir . '/routes/channels.php';
        if (! file_exists($channelsPath)) {
            @file_put_contents(
                $channelsPath,
                "<?php\n\nuse Illuminate\Support\Facades\Broadcast;\nuse WireNinja\Prasmanan\Facades\PrasmananBroadcast;\n\nPrasmananBroadcast::all();\n"
            );
        }

        // 6. Update bootstrap/app.php
        $appPath = $baseDir . '/bootstrap/app.php';
        if (file_exists($appPath)) {
            $appContent = file_get_contents($appPath);
            
            // Safer injection for withChannels
            if (! str_contains($appContent, 'withChannels')) {
                $search = "commands: __DIR__.'/../routes/console.php',";
                $replace = $search . "\n        channels: __DIR__.'/../routes/channels.php',";
                if (str_contains($appContent, $search)) {
                    $appContent = str_replace($search, $replace, $appContent);
                } else {
                    // fallback to more generic match
                    $appContent = preg_replace(
                        '/commands:\s*__DIR__\.\'\/..\/routes\/console\.php\',?/m',
                        "commands: __DIR__.'/../routes/console.php',\n        channels: __DIR__.'/../routes/channels.php',",
                        $appContent
                    );
                }
            }

            // Exception handling
            if (! str_contains($appContent, 'dontReportForGuestUser')) {
                $appContent = preg_replace(
                    '/withExceptions\(function\s*\(Exceptions\s*\$exceptions\)\s*\{/m',
                    "withExceptions(function (Exceptions \$exceptions) {\n        \WireNinja\Prasmanan\Supports\PrasmananExceptions::dontReportForGuestUser(\$exceptions);",
                    $appContent
                );
            }
            file_put_contents($appPath, $appContent);
        }

        // 7. Execute Artisan commands safely
        $artisanPath = $baseDir . '/artisan';
        if (file_exists($artisanPath)) {
            @exec("php $artisanPath lang:add id > /dev/null 2>&1");
            @exec("php $artisanPath lang:update > /dev/null 2>&1");
        }

        // Mark as initialized
        @file_put_contents($lockFile, date('Y-m-d H:i:s'));
    }
}
