<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Console\Commands\System;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use WireNinja\Prasmanan\Supports\PrasmananConstants;

final class EjectCommand extends Command
{
    protected $signature = 'prasmanan:eject {config? : The vendor config name to eject (e.g. filament-shield)}';

    protected $description = 'Eject an opinionated vendor configuration to user-land for customization.';

    public function handle(): int
    {
        $configName = $this->argument('config');

        if (! $configName) {
            return $this->showAvailableConfigs();
        }

        $src = PrasmananConstants::configDir() . "/vendors/{$configName}.php";
        $destDir = config_path('prasmanan/vendors');
        $dest = "{$destDir}/{$configName}.php";

        if (! File::exists($src)) {
            $this->components->error("Config [{$configName}] not found in Prasmanan vendors.");
            return self::FAILURE;
        }

        if (File::exists($dest)) {
            if (! $this->confirm("File [config/prasmanan/vendors/{$configName}.php] already exists. Overwrite?", false)) {
                $this->components->warn('Aborted.');
                return self::SUCCESS;
            }
        }

        File::ensureDirectoryExists($destDir);
        File::copy($src, $dest);

        $this->components->info("✓ Successfully ejected [{$configName}] to [config/prasmanan/vendors/{$configName}.php]");
        $this->components->info("Next: Use \$this->reconfigureVendor('{$configName}') in your AppServiceProvider.");

        return self::SUCCESS;
    }

    private function showAvailableConfigs(): int
    {
        $vendorDir = PrasmananConstants::configDir() . '/vendors';

        if (! File::exists($vendorDir)) {
            $this->components->error('Prasmanan vendor configuration directory not found.');
            return self::FAILURE;
        }

        $files = File::files($vendorDir);
        $configs = collect($files)->map(fn($file) => $file->getBasename('.php'))->toArray();

        $this->components->info('Available vendor configurations for ejection:');
        foreach ($configs as $config) {
            $this->line(" - <fg=yellow>{$config}</>");
        }

        return self::SUCCESS;
    }
}
