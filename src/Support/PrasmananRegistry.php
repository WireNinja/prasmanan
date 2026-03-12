<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Support;

final class PrasmananRegistry
{
    /**
     * @var array<string>
     */
    private static array $reconfiguredVendors = [];

    /**
     * Register a vendor configuration to be reconfigured by the user.
     */
    public static function reconfigureVendor(string $name): void
    {
        if (! in_array($name, self::$reconfiguredVendors, true)) {
            self::$reconfiguredVendors[] = $name;
        }
    }

    /**
     * Check if a vendor configuration is marked for reconfiguration.
     */
    public static function isReconfigured(string $name): bool
    {
        return in_array($name, self::$reconfiguredVendors, true);
    }

    /**
     * Get all reconfigured vendors.
     * 
     * @return array<string>
     */
    public static function getReconfiguredVendors(): array
    {
        return self::$reconfiguredVendors;
    }
}
