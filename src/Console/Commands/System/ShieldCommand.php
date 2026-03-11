<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Console\Commands\System;

use Illuminate\Console\Command;

final class ShieldCommand extends Command
{
    protected $signature = 'prasmanan:system-shield';

    protected $description = 'Generate Filament Shield policies and permissions for the admin panel.';

    public function handle(): int
    {
        $this->components->info('Generating Filament Shield policies and permissions...');

        $this->call('shield:generate', [
            '--all' => true,
            '--ignore-existing-policies' => true,
            '--option' => 'policies_and_permissions',
        ]);

        $this->components->info('✓ Shield generation completed!');

        return self::SUCCESS;
    }
}
