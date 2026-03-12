<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Console\Commands\System;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use WireNinja\Prasmanan\Supports\PrasmananConstants;

final class FormatCommand extends Command
{
    protected $signature = 'prasmanan:system-format {--dirty : Only fix files that have uncommitted changes}';

    protected $description = 'Run Pint code formatter using Prasmanan opinionated configuration.';

    public function handle(): int
    {
        $this->components->info('Running Prasmanan Code Formatter (Pint)...');

        $configPath = PrasmananConstants::configsDir().'/pint.json';
        $binaryPath = base_path('vendor/bin/pint');

        if (! file_exists($binaryPath)) {
            $this->components->error('Pint binary not found in vendor/bin/pint. Please run composer install.');

            return self::FAILURE;
        }

        $command = [$binaryPath, '--config', $configPath];

        if ($this->option('dirty')) {
            $command[] = '--dirty';
        }

        $process = new Process($command, base_path(), timeout: null);
        $process->setTty(Process::isTtySupported());

        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if ($process->isSuccessful()) {
            $this->components->info('✓ Code formatting completed!');

            return self::SUCCESS;
        }

        $this->components->error('Formatting failed.');

        return self::FAILURE;
    }
}
