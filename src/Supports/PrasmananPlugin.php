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
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::PRE_AUTOLOAD_DUMP => 'ensureDirectoriesExist',
            ScriptEvents::PRE_UPDATE_CMD => 'ensureDirectoriesExist',
            ScriptEvents::PRE_INSTALL_CMD => 'ensureDirectoriesExist',
            ScriptEvents::POST_UPDATE_CMD => 'initializeFreshProject',
            ScriptEvents::POST_INSTALL_CMD => 'initializeFreshProject',
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

        if (file_exists($lockFile)) {
            return;
        }

        // Get stubs directory via reflection to be safe from Composer path rewriting
        $reflector = new \ReflectionClass(static::class);
        $stubsDir = dirname($reflector->getFileName()) . '/../../stubs';

        // 1. Copy PWA Assets
        $pwaAssets = [
            'pwa-assets.config.stub' => 'pwa-assets.config.ts',
            'pwa-iconify-fetch.js.stub' => 'pwa-iconify-fetch.js',
            'pwa-icons-copy.js.stub' => 'pwa-icons-copy.js',
            'pwa-vite.config.stub' => 'pwa-vite.config.ts'
        ];

        foreach ($pwaAssets as $stub => $dest) {
            $src = $stubsDir . '/pwa/' . $stub;
            $dsc = $baseDir . '/' . $dest;
            if (file_exists($src) && ! file_exists($dsc)) {
                @copy($src, $dsc);
            }
        }

        // 2. Add Package.json Scripts
        $packageJsonPath = $baseDir . '/package.json';
        if (file_exists($packageJsonPath)) {
            $content = file_get_contents($packageJsonPath);
            $packageJson = json_decode($content, true);
            if (is_array($packageJson)) {
                if (! isset($packageJson['scripts']['pwa:assets'])) {
                    $packageJson['scripts']['iconify:fetch'] = 'bun pwa-iconify-fetch.js';
                    $packageJson['scripts']['pwa:assets'] = 'bun pwa-assets-generator --preset minimal public/favicon.svg';
                    $packageJson['scripts']['pwa:icons'] = 'bun run pwa:iconify && bun run pwa:copy';
                    $packageJson['scripts']['pwa:iconify'] = 'bun pwa-iconify-fetch.js';
                    $packageJson['scripts']['pwa:copy'] = 'bun pwa-icons-copy.js';
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
            @file_put_contents($envPath, $envCont);
        }

        // 4. Replace Vite Config
        $vitePath = $baseDir . '/vite.config.js';
        $viteStub = $stubsDir . '/vite.config.js.stub';
        if (file_exists($vitePath) && file_exists($viteStub)) {
            $currentVite = file_get_contents($vitePath);
            if (! str_contains($currentVite, 'getViteInputs')) {
                @copy($viteStub, $vitePath);
            }
        }

        // 5. Create routes/channels.php
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
            
            // Channels registration - using careful str_replace
            if (! str_contains($appContent, 'withChannels') && ! str_contains($appContent, 'channels:')) {
                $search = "commands: __DIR__.'/../routes/console.php',";
                if (str_contains($appContent, $search)) {
                    $appContent = str_replace($search, $search . "\n        channels: __DIR__.'/../routes/channels.php',", $appContent);
                }
            }

            // Exception handling
            if (! str_contains($appContent, 'dontReportForGuestUser')) {
                $search = "withExceptions(function (Exceptions \$exceptions) {";
                if (str_contains($appContent, $search)) {
                     $appContent = str_replace($search, $search . "\n        \WireNinja\Prasmanan\Supports\PrasmananExceptions::dontReportForGuestUser(\$exceptions);", $appContent);
                }
            }
            @file_put_contents($appPath, $appContent);
        }

        // 7. Execute Artisan commands
        $artisanPath = $baseDir . '/artisan';
        if (file_exists($artisanPath)) {
            @exec("php $artisanPath lang:add id > /dev/null 2>&1");
            @exec("php $artisanPath lang:update > /dev/null 2>&1");
        }

        // Mark as initialized
        if (is_dir($baseDir . '/storage')) {
            @file_put_contents($lockFile, date('Y-m-d H:i:s'));
        }
    }
}
