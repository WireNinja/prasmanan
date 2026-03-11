<?php

namespace WireNinja\Prasmanan\Supports;

class ComposerHooks
{
    /**
     * Ensure mandatory directories exist to prevent BladeIcons or other 
     * packages from crashing during composer update/install.
     */
    public static function setup(mixed $event = null): void
    {
        $directories = [
            'resources/svg',
            'resources/css/filament',
            'lang/id',
        ];

        foreach ($directories as $dir) {
            $path = getcwd() . DIRECTORY_SEPARATOR . $dir;
            
            if (! is_dir($path)) {
                if (mkdir($path, 0755, true)) {
                    // Create an empty .gitkeep to ensure the directory is tracked by git
                    file_put_contents($path . DIRECTORY_SEPARATOR . '.gitkeep', '');
                }
            }
        }
    }
}
