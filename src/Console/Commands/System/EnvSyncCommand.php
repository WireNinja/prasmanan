<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Console\Commands\System;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use WireNinja\Prasmanan\Supports\PrasmananConstants;

final class EnvSyncCommand extends Command
{
    protected $signature = 'prasmanan:env-sync {--force : Force overwrite .env.example}';

    protected $description = 'Sync .env.example with Prasmanan opinionated defaults.';

    public function handle(): int
    {
        $targetPath = base_path('.env.example');
        $stubPath = PrasmananConstants::stubsDir().'/.env.example.stub';

        if (! File::exists($stubPath)) {
            $this->components->error('Stub for .env.example missing!');

            return self::FAILURE;
        }

        if (File::exists($targetPath) && ! $this->option('force')) {
            if (! $this->confirm('.env.example already exists. Overwrite?', false)) {
                $this->components->info('Sync cancelled.');

                return self::SUCCESS;
            }
        }

        $this->components->task('Syncing .env.example...', function () use ($stubPath, $targetPath) {
            return File::copy($stubPath, $targetPath);
        });

        $this->components->info('✓ .env.example synced successfully!');

        return self::SUCCESS;
    }
}
