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
        $lockFile = $baseDir . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . '.prasmanan_initialized';

        // Only run initialization once per project.
        if (file_exists($lockFile)) {
            return;
        }

        // 1. Copy PWA Assets
        $stubsDir = __DIR__ . '/../../stubs/';
        $pwaAssets = [
            'pwa-assets.config.ts',
            'pwa-iconify-fetch.js',
            'pwa-icons-copy.js',
            'pwa-vite.config.ts'
        ];

        foreach ($pwaAssets as $asset) {
            if (! file_exists($baseDir . '/' . $asset) && file_exists($stubsDir . 'pwa/' . $asset)) {
                @copy($stubsDir . 'pwa/' . $asset, $baseDir . '/' . $asset);
            }
        }

        // 2. Add Package.json Scripts
        $packageJsonPath = $baseDir . '/package.json';
        if (file_exists($packageJsonPath)) {
            $packageJson = json_decode(file_get_contents($packageJsonPath), true);
            if (is_array($packageJson) && ! isset($packageJson['scripts']['pwa:assets'])) {
                $packageJson['scripts']['iconify:fetch'] = 'bun pwa-iconify-fetch.js';
                $packageJson['scripts']['pwa:assets'] = 'bun pwa-assets-generator --preset minimal public/favicon.svg';
                $packageJson['scripts']['pwa:icons'] = 'bun run pwa:iconify && bun run pwa:copy';
                $packageJson['scripts']['pwa:iconify'] = 'bun pwa-iconify-fetch.js';
                $packageJson['scripts']['pwa:copy'] = 'bun pwa-icons-copy.js';
                file_put_contents($packageJsonPath, json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
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

        // 4. Replace Vite Config
        $vitePath = $baseDir . '/vite.config.js';
        if (file_exists($vitePath) && ! str_contains(file_get_contents($vitePath), 'getViteInputs')) {
            if (file_exists($stubsDir . 'vite.config.js.stub')) {
                @copy($stubsDir . 'vite.config.js.stub', $vitePath);
            }
        }

        // 5. Create routes/channels.php if missing
        $channelsPath = $baseDir . '/routes/channels.php';
        if (! file_exists($channelsPath)) {
            file_put_contents(
                $channelsPath,
                "<?php\n\nuse Illuminate\Support\Facades\Broadcast;\nuse WireNinja\Prasmanan\Facades\PrasmananBroadcast;\n\nPrasmananBroadcast::all();\n"
            );
        }

        // 6. Update bootstrap/app.php
        $appPath = $baseDir . '/bootstrap/app.php';
        if (file_exists($appPath)) {
            $appContent = file_get_contents($appPath);
            if (! str_contains($appContent, 'withChannels')) {
                // inject withChannels: __DIR__.'/../routes/channels.php' after commands: __DIR__.'/../routes/console.php',
                $appContent = preg_replace(
                    '/commands:\s*__DIR__\.\'\/..\/routes\/console\.php\',/m',
                    "commands: __DIR__.'/../routes/console.php',\n        channels: __DIR__.'/../routes/channels.php',",
                    $appContent
                );
            }

            if (! str_contains($appContent, 'dontReportForGuestUser')) {
                // inject exception handling
                $appContent = preg_replace(
                    '/withExceptions\(function\s*\(\w+\s*\$exceptions\)\s*\{/m',
                    "withExceptions(function (Exceptions \$exceptions) {\n        \WireNinja\Prasmanan\Supports\PrasmananExceptions::dontReportForGuestUser(\$exceptions);",
                    $appContent
                );
            }
            file_put_contents($appPath, $appContent);
        }

        // 7. Execute Artisan commands safely
        $artisanPath = $baseDir . '/artisan';
        if (file_exists($artisanPath)) {
            @exec("php $artisanPath lang:add id");
            @exec("php $artisanPath lang:update");
        }

        // Mark as initialized
        @file_put_contents($lockFile, date('Y-m-d H:i:s'));
    }
}
