<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Supports;

final class PrasmananConstants
{
    /**
     * PWA Assets
     */
    public const PWA_ICON_192 = '/pwa/icons/pwa-192x192.png';

    public const PWA_ICON_512 = '/pwa/icons/pwa-512x512.png';

    public const PWA_BADGE_64 = '/pwa/icons/pwa-64x64.png';

    /**
     * Root Directory of the package
     */
    public static function rootDir(): string
    {
        return realpath(__DIR__.'/../../');
    }

    /**
     * Stubs Directory
     */
    public static function stubsDir(): string
    {
        return self::rootDir().'/stubs';
    }

    /**
     * Settings Migration Directory
     */
    public static function settingsMigrationDir(): string
    {
        return self::rootDir().'/database/settings';
    }

    /**
     * Config Directory
     */
    public static function configDir(): string
    {
        return self::rootDir().'/config';
    }
}
