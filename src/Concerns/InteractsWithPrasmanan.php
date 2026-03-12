<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Concerns;

use WireNinja\Prasmanan\Support\PrasmananRegistry;

trait InteractsWithPrasmanan
{
    protected function reconfigureVendor(string $name): void
    {
        $userLandPath = config_path("prasmanan/vendors/{$name}.php");

        if (! file_exists($userLandPath)) {
            throw new \RuntimeException("Prasmanan: Failed to reconfigure vendor [{$name}]. File not found at [config/prasmanan/vendors/{$name}.php]. Did you run 'php artisan prasmanan:eject {$name}'?");
        }

        PrasmananRegistry::reconfigureVendor($name);

        $config = config($name, []);
        $override = require $userLandPath;

        config()->set($name, array_replace_recursive($config, $override));
    }
}
