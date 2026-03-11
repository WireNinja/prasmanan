<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Console\Commands\System;

use Illuminate\Console\Command;

final class RefreshCommand extends Command
{
    protected $signature = 'prasmanan:system-refresh';

    protected $description = 'Refresh the database (migrate:fresh --seed).';

    public function handle(): int
    {
        $this->components->warn('Refreshing database (migrate:fresh --seed)...');

        $this->call('migrate:fresh', [
            '--seed' => true,
        ]);

        $this->components->info('✓ Database refresh completed!');

        return self::SUCCESS;
    }
}
