<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Console\Commands\Enum;

use BackedEnum;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use WireNinja\Prasmanan\Concerns\InteractsWithEnums;

class GenerateEnumAnnotations extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'prasmanan:enum-annotate
                            {enum? : Specific enum class to annotate (e.g. StockTransferStatusEnum)}
                            {--all : Annotate all enums}
                            {--dry-run : Show what would be generated without writing}';

    /**
     * The console command description.
     */
    protected $description = 'Generate PHPDoc annotations for enums based on their cases';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->hasValidInput()) {
            $this->error('Please specify an enum or use --all to annotate all enums');

            return self::FAILURE;
        }

        $this->info('Starting enum annotation generation...');
        $this->newLine();

        foreach ($this->getEnumsToProcess() as $enumClass) {
            $this->processSingleEnum($enumClass);
        }

        $this->newLine();
        $this->info('✓ Enum annotation generation completed!');

        return self::SUCCESS;
    }

    /**
     * Check if the command has valid input.
     */
    private function hasValidInput(): bool
    {
        return $this->argument('enum') || $this->option('all');
    }

    /**
     * Get the list of enums to process.
     *
     * @return array<int, string>
     */
    private function getEnumsToProcess(): array
    {
        return $this->option('all')
            ? $this->discoverEnums()
            : [$this->resolveEnumClass($this->argument('enum'))];
    }

    /**
     * Process a single enum class.
     */
    private function processSingleEnum(string $enumClass): void
    {
        if (! enum_exists($enumClass)) {
            $this->warn("Skipping {$enumClass}: Not a valid Enum");

            return;
        }

        $this->info("Processing: {$enumClass}");

        if (! $this->usesInteractsWithEnums($enumClass)) {
            $this->warn('  ⚠ Skipping: Does not use InteractsWithEnums trait');

            return;
        }

        $annotations = $this->generateAnnotations($enumClass);

        if ($this->option('dry-run')) {
            $this->displayDryRun($annotations);

            return;
        }

        $this->applyAnnotations($enumClass, $annotations);
    }

    /**
     * Check if the enum class uses the InteractsWithEnums trait.
     */
    private function usesInteractsWithEnums(string $enumClass): bool
    {
        $traits = class_uses_recursive($enumClass);

        return in_array(InteractsWithEnums::class, $traits);
    }

    /**
     * Generate annotations for the given enum class.
     *
     * @return array<int, string>
     */
    private function generateAnnotations(string $enumClass): array
    {
        /** @var BackedEnum|string $enumClass */
        $cases = $enumClass::cases();
        $annotations = [];

        foreach ($cases as $case) {
            $name = $case->name;
            $annotations[] = "@method bool is{$name}()";
            $annotations[] = "@method bool isNot{$name}()";

            if (method_exists($enumClass, 'canTransitionTo')) {
                $annotations[] = "@method bool canTransitionTo{$name}()";
            }
        }

        return $annotations;
    }

    /**
     * Display the generated annotations for dry-run.
     *
     * @param  array<int, string>  $annotations
     */
    private function displayDryRun(array $annotations): void
    {
        $this->line('  Generated annotations:');
        $this->line('  '.str_repeat('-', 60));

        foreach ($annotations as $line) {
            $this->line('  '.$line);
        }

        $this->line('  '.str_repeat('-', 60));
    }

    /**
     * Apply the generated annotations to the enum file.
     *
     * @param  array<int, string>  $annotations
     */
    private function applyAnnotations(string $enumClass, array $annotations): void
    {
        $reflection = new ReflectionClass($enumClass);

        $this->writeAnnotations($reflection, $annotations);

        $this->comment("  ✓ Annotations written to {$reflection->getFileName()}");
    }

    /**
     * Write annotations to the enum file.
     */
    private function writeAnnotations(ReflectionClass $reflection, array $annotations): void
    {
        $filePath = $reflection->getFileName();

        if (! $filePath) {
            return;
        }

        $content = File::get($filePath);
        $lines = explode("\n", $content);

        $enumLine = array_find_key($lines, function ($line) use ($reflection): int|false {
            return preg_match('/^enum\s+'.preg_quote($reflection->getShortName(), '/').'/', trim($line));
        });

        if ($enumLine === null) {
            return;
        }

        $this->removeExistingDocBlock($lines, $enumLine);

        $docBlock = $this->buildNewDocBlock($annotations);

        array_splice($lines, $enumLine, 0, $docBlock);

        File::put($filePath, implode("\n", $lines));
    }

    /**
     * Remove existing PHPDoc block from the lines.
     */
    private function removeExistingDocBlock(array &$lines, int &$enumLine): void
    {
        $docBlockStart = null;
        $docBlockEnd = null;

        for ($i = $enumLine - 1; $i >= 0; $i--) {
            $trimmed = trim($lines[$i]);

            if ($trimmed === '*/') {
                $docBlockEnd = $i;
            }

            if ($trimmed === '/**' || str_starts_with($trimmed, '/**')) {
                $docBlockStart = $i;
                break;
            }

            if ($trimmed !== '' && ! str_starts_with($trimmed, '*') && $docBlockEnd !== null) {
                break;
            }
        }

        if ($docBlockStart !== null && $docBlockEnd !== null) {
            array_splice($lines, $docBlockStart, $docBlockEnd - $docBlockStart + 1);
            $enumLine -= ($docBlockEnd - $docBlockStart + 1);
        }
    }

    /**
     * Build the new PHPDoc block.
     *
     * @param  array<int, string>  $annotations
     * @return array<int, string>
     */
    private function buildNewDocBlock(array $annotations): array
    {
        $docBlock = ['/**'];

        foreach ($annotations as $annotation) {
            $docBlock[] = ' * '.$annotation;
        }

        $docBlock[] = ' */';

        return $docBlock;
    }

    /**
     * Discover all enums in app/Enums directory.
     */
    private function discoverEnums(): array
    {
        $enumsPath = app_path('Enums');
        $enums = [];

        if (! File::isDirectory($enumsPath)) {
            return [];
        }

        $files = File::allFiles($enumsPath);

        foreach ($files as $file) {
            $relative = Str::after($file->getPathname(), app_path('Enums').DIRECTORY_SEPARATOR);
            $class = 'App\\Enums\\'.str_replace(
                ['/', '.php'],
                ['\\', ''],
                $relative
            );

            if (enum_exists($class)) {
                $enums[] = $class;
            }
        }

        return $enums;
    }

    /**
     * Resolve enum class from name.
     */
    private function resolveEnumClass(string $name): string
    {
        if (enum_exists($name)) {
            return $name;
        }

        $class = 'App\\Enums\\'.$name;

        if (! enum_exists($class)) {
            $this->error("Enum {$name} not found");
            exit(1);
        }

        return $class;
    }
}
