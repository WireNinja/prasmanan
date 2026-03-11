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
}
