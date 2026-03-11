<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Console\Commands\System;

use Illuminate\Console\Command;

final class RefreshCommand extends Command
{
    protected $signature = 'prasmanan:system-refresh';

    protected $description = 'Refresh the database and run preparation tasks.';

    public function handle(): int
    {
        $this->components->warn('Refreshing database (migrate:fresh --seed)...');

        // 1. Migrate Fresh
        $this->call('migrate:fresh', [
            '--seed' => true,
        ]);

        // 2. Run Prepare
        $this->call('prasmanan:system-prepare');

        $this->components->info('✓ Database refresh and system preparation completed!');

        return self::SUCCESS;
    }
}
