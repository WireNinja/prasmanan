<?php

namespace WireNinja\Prasmanan\Console\Commands\Env;

use Illuminate\Console\Command;

class PrintCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prasmanan:env-print';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Print the .env file with redacted sensitive information';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->passesPreChecks()) {
            return self::FAILURE;
        }

        $this->info('Printing .env file (Sensitive values redacted):');
        $this->newLine();

        $this->printEnvContent($this->getEnvPath());

        return self::SUCCESS;
    }

    /**
     * Perform pre-check validation.
     */
    private function passesPreChecks(): bool
    {
        if (! app()->isLocal()) {
            $this->error('This command can only be executed in local environment.');

            return false;
        }

        if (! config('app.debug')) {
            $this->error('This command can only be executed when debug mode is enabled.');

            return false;
        }

        if (! file_exists($this->getEnvPath())) {
            $this->error('.env file not found at '.$this->getEnvPath());

            return false;
        }

        return true;
    }

    /**
     * Get the absolute path to the .env file.
     */
    private function getEnvPath(): string
    {
        return base_path('.env');
    }

    /**
     * Print the content of the .env file with redactions.
     */
    private function printEnvContent(string $path): void
    {
        $content = file_get_contents($path);
        $lines = explode("\n", (string) $content);

        foreach ($lines as $line) {
            $this->line($this->processLine($line));
        }
    }

    /**
     * Process a single line from the .env file.
     */
    private function processLine(string $line): string
    {
        $trimmedLine = trim($line);

        if ($this->isIgnorable($trimmedLine)) {
            return $line;
        }

        if (str_contains($trimmedLine, '=')) {
            return $this->redactIfSensitive($line, $trimmedLine);
        }

        return $line;
    }

    /**
     * Check if the line is empty or a comment.
     */
    private function isIgnorable(string $trimmedLine): bool
    {
        return $trimmedLine === '' || $trimmedLine === '0' || str_starts_with($trimmedLine, '#');
    }

    /**
     * Redact the line if it contains sensitive information.
     */
    private function redactIfSensitive(string $originalLine, string $trimmedLine): string
    {
        $parts = explode('=', $trimmedLine, 2);
        $key = trim($parts[0]);

        if ($this->shouldRedact($key)) {
            return "{$key}=[REDACTED]";
        }

        return $originalLine;
    }

    /**
     * Determine if a key should be redacted.
     */
    private function shouldRedact(string $key): bool
    {
        $keywords = ['key', 'secret', 'id', 'password', 'token', 'cert', 'auth', 'private'];

        return array_any(
            $keywords,
            fn ($keyword): bool => str_contains(strtolower($key), $keyword)
        );
    }
}
