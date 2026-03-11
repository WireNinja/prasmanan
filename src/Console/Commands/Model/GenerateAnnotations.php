<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Console\Commands\Model;

use Brick\Math\BigDecimal;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use WireNinja\Prasmanan\Concerns\MagicGetterSetter;

class GenerateAnnotations extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'prasmanan:model-annotate
                            {model? : Specific model class to annotate (e.g. User)}
                            {--all : Annotate all models}
                            {--dry-run : Show what would be generated without writing}';

    /**
     * The console command description.
     */
    protected $description = 'Generate PHPDoc annotations for models based on database schema and methods';

    /**
     * Execute the console command.
     */
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->hasValidInput()) {
            $this->error('Please specify a model or use --all to annotate all models');

            return self::FAILURE;
        }

        $this->info('Starting model annotation generation...');
        $this->newLine();

        $this->processModels($this->getModelsToProcess());

        $this->newLine();
        $this->info('✓ Model annotation generation completed!');

        return self::SUCCESS;
    }

    /**
     * Check if the command has valid input.
     */
    private function hasValidInput(): bool
    {
        return $this->argument('model') || $this->option('all');
    }

    /**
     * Get the list of models to process.
     *
     * @return array<int, string>
     */
    private function getModelsToProcess(): array
    {
        return $this->option('all')
            ? $this->discoverModels()
            : [$this->resolveModelClass($this->argument('model'))];
    }

    /**
     * Process a list of model classes.
     *
     * @param  array<int, string>  $models
     */
    private function processModels(array $models): void
    {
        foreach ($models as $modelClass) {
            if (! $this->isValidModel($modelClass)) {
                $this->warn("Skipping {$modelClass}: Not a valid Eloquent model");

                continue;
            }

            $this->processModel($modelClass, $this->option('dry-run'));
        }
    }

    /**
     * Process a single model class.
     */
    private function processModel(string $modelClass, bool $dryRun): void
    {
        $this->info("Processing: {$modelClass}");

        $model = new $modelClass;
        $reflection = new ReflectionClass($modelClass);

        $columns = Schema::getColumns($model->getTable());
        $casts = $this->getCasts($model);

        $builderFQCN = $this->ensureQueryBuilder($reflection, $dryRun);

        $this->ensureRelatedClasses($reflection, $columns, $casts, $dryRun);

        $annotations = $this->generateAnnotations($reflection, $columns, $casts, $builderFQCN);

        if ($dryRun) {
            $this->displayDryRun($annotations);

            return;
        }

        $this->applyModelAnnotations($reflection, $annotations);
    }

    /**
     * Ensure related classes like Entity and Optional Entity exist.
     */
    private function ensureRelatedClasses(ReflectionClass $reflection, array $columns, array $casts, bool $dryRun): void
    {
        $this->ensureEntity($reflection, $columns, $casts, $dryRun);
        $this->ensureOptionalEntity($reflection, $columns, $casts, $dryRun);
    }

    /**
     * Display generated annotations for dry-run.
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
     * Apply annotations to the model file.
     *
     * @param  array<int, string>  $annotations
     */
    private function applyModelAnnotations(ReflectionClass $reflection, array $annotations): void
    {
        $this->writeAnnotations($reflection, $annotations);

        $this->comment("  ✓ Annotations and attribute written to {$reflection->getFileName()}");
    }

    /**
     * Generate all annotations for a model.
     *
     * @return array<int, string>
     */
    private function generateAnnotations(
        ReflectionClass $reflection,
        array $columns,
        array $casts,
        ?string $builderClass = null
    ): array {
        $annotations = [];

        $annotations = array_merge($annotations, $this->generatePropertyAnnotations($columns, $casts));
        $annotations = array_merge($annotations, $this->generateRelationshipAnnotations($reflection));
        $annotations = array_merge($annotations, $this->generateAccessorAnnotations($reflection));
        $annotations = array_merge($annotations, $this->generateScopeAnnotations($reflection));

        if ($builderClass) {
            $annotations = array_merge($annotations, $this->generateQueryBuilderAnnotation($builderClass));
        }

        if ($this->usesMagicGetterSetter($reflection)) {
            $annotations = array_merge($annotations, $this->generateMagicMethodAnnotations($reflection, $columns, $casts));
        }

        return $annotations;
    }

    /**
     * Generate property annotations for database columns.
     */
    private function generatePropertyAnnotations(array $columns, array $casts): array
    {
        $annotations = [];

        foreach ($columns as $column) {
            $name = $column['name'];
            $type = $this->mapDatabaseTypeToPhp($column, $casts[$name] ?? null);
            $nullable = $column['nullable'] ? '|null' : '';

            $annotations[] = "@property {$type}{$nullable} \${$name}";
        }

        return $annotations;
    }

    /**
     * Generate property-read annotations for relationships.
     */
    private function generateRelationshipAnnotations(ReflectionClass $reflection): array
    {
        $annotations = [];
        $relationships = $this->detectRelationships($reflection);

        foreach ($relationships as $name => $relationshipInfo) {
            $annotations[] = "@property-read {$relationshipInfo['return_type']} \${$name}";
        }

        return $annotations;
    }

    /**
     * Generate property-read annotations for accessors.
     */
    private function generateAccessorAnnotations(ReflectionClass $reflection): array
    {
        $annotations = [];
        $accessors = $this->detectAccessors($reflection);

        foreach ($accessors as $name => $returnType) {
            $annotations[] = "@property-read {$returnType} \${$name}";
        }

        return $annotations;
    }

    /**
     * Generate static method annotations for scopes.
     */
    private function generateScopeAnnotations(ReflectionClass $reflection): array
    {
        $annotations = [];
        $scopes = $this->detectScopes($reflection);

        foreach ($scopes as $scopeName => $method) {
            $params = $this->getMethodParameters($method);
            $annotations[] = "@method static \\Illuminate\\Database\\Eloquent\\Builder {$scopeName}({$params})";
        }

        return $annotations;
    }

    /**
     * Generate query() method annotation for custom QueryBuilder.
     */
    private function generateQueryBuilderAnnotation(string $builderClass): array
    {
        $builderShortName = class_basename($builderClass);

        return [
            '',
            "@method static {$builderShortName} query()",
        ];
    }

    /**
     * Check if the model uses MagicGetterSetter trait.
     */
    private function usesMagicGetterSetter(ReflectionClass $reflection): bool
    {
        return in_array(MagicGetterSetter::class, class_uses_recursive($reflection->getName()));
    }

    /**
     * Generate magic getter/setter method annotations.
     */
    private function generateMagicMethodAnnotations(ReflectionClass $reflection, array $columns, array $casts): array
    {
        $annotations = [''];

        foreach ($columns as $column) {
            $name = $column['name'];
            $studlyName = Str::studly($name);
            $type = $this->mapDatabaseTypeToPhp($column, $casts[$name] ?? null);
            $nullable = $column['nullable'] ? '|null' : '';

            $annotations[] = "@method {$type}{$nullable} get{$studlyName}()";
            $annotations[] = "@method self set{$studlyName}({$type}{$nullable} \${$name})";
        }

        foreach ($this->detectRelationships($reflection) as $name => $relationshipInfo) {
            $studlyName = Str::studly($name);
            $annotations[] = "@method {$relationshipInfo['return_type']} get{$studlyName}()";
        }

        foreach ($this->detectAccessors($reflection) as $name => $returnType) {
            $studlyName = Str::studly($name);
            $annotations[] = "@method {$returnType} get{$studlyName}()";
        }

        return $annotations;
    }

    /**
     * Map database column type to PHP type.
     */
    private function mapDatabaseTypeToPhp(array $column, ?string $cast): string
    {
        // If there's a cast, respect it
        if ($cast) {
            // Check if it's an enum cast (e.g., StatusEnum::class)
            if (enum_exists($cast)) {
                return '\\'.$cast;
            }

            // Handle custom cast classes (e.g., BigDecimalCast)
            if (str_contains($cast, 'BigDecimalCast')) {
                return '\\'.BigDecimal::class;
            }

            // Handle built-in Laravel casts
            return match ($cast) {
                'int', 'integer' => 'int',
                'real', 'float', 'double' => 'float',
                'string' => 'string',
                'bool', 'boolean' => 'bool',
                'object' => 'object',
                'array', 'json' => 'array',
                'collection' => '\\'.Collection::class,
                'date', 'datetime' => '\\'.Carbon::class,
                'immutable_date', 'immutable_datetime' => '\\'.CarbonImmutable::class,
                'timestamp' => 'int',
                'decimal' => BigDecimal::class,
                'hashed' => 'string',
                default => 'mixed',
            };
        }

        // Map based on database type
        $dbType = strtolower($column['type_name']);

        return match (true) {
            str_contains($dbType, 'int') => 'int',
            str_contains($dbType, 'decimal') || str_contains($dbType, 'numeric') => '\\'.BigDecimal::class,
            str_contains($dbType, 'float') || str_contains($dbType, 'double') || str_contains($dbType, 'real') => 'float',
            str_contains($dbType, 'bool') => 'bool',
            str_contains($dbType, 'json') => 'array',
            str_contains($dbType, 'text') || str_contains($dbType, 'char') || str_contains($dbType, 'varchar') => 'string',
            str_contains($dbType, 'date') || str_contains($dbType, 'time') => '\\'.Carbon::class,
            str_contains($dbType, 'blob') || str_contains($dbType, 'binary') => 'string',
            default => 'mixed',
        };
    }

    /**
     * Get casts from model.
     *
     * @return array<string, string>
     */
    private function getCasts(Model $model): array
    {
        $casts = $model->getCasts();

        // Normalize cast types
        return array_map(function ($cast) {
            // Handle array notation like "decimal:2"
            if (str_contains($cast, ':')) {
                [$type] = explode(':', $cast, 2);

                return $type;
            }

            return $cast;
        }, $casts);
    }

    /**
     * Detect all relationships in the model.
     *
     * @return array<string, array{method: string, return_type: string}>
     */
    private function detectRelationships(ReflectionClass $reflection): array
    {
        $relationships = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $returnType = $method->getReturnType();
            if (! $returnType instanceof ReflectionNamedType) {
                continue;
            }

            $returnTypeName = $returnType->getName();

            // Check if it's a relationship
            if (is_subclass_of($returnTypeName, Relation::class)) {
                $relationshipType = $this->mapRelationshipType($returnTypeName, $method);
                $relationships[$method->getName()] = [
                    'method' => $method->getName(),
                    'return_type' => $relationshipType,
                ];
            }
        }

        return $relationships;
    }

    /**
     * Map relationship class to property type.
     */
    private function mapRelationshipType(string $relationClass, ReflectionMethod $method): string
    {
        // Try to infer the related model from method body
        $relatedModel = $this->inferRelatedModel($method);

        return match (class_basename($relationClass)) {
            'HasOne', 'BelongsTo', 'MorphOne' => $relatedModel ? "?{$relatedModel}" : '?\\Illuminate\\Database\\Eloquent\\Model',
            'HasMany', 'BelongsToMany', 'MorphMany', 'MorphToMany', 'HasManyThrough' => $relatedModel ? "\\Illuminate\\Database\\Eloquent\\Collection<int, {$relatedModel}>" : '\\'.Collection::class,
            'MorphTo' => '?\\Illuminate\\Database\\Eloquent\\Model',
            default => '\\'.Collection::class,
        };
    }

    /**
     * Infer related model from relationship method.
     */
    private function inferRelatedModel(ReflectionMethod $method): ?string
    {
        $filePath = $method->getFileName();
        if (! $filePath) {
            return null;
        }

        $content = File::get($filePath);
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();

        $lines = explode("\n", $content);
        $methodBody = implode("\n", array_slice($lines, $startLine - 1, $endLine - $startLine + 1));

        // Try to find $this->hasMany(Model::class), $this->belongsTo(Model::class), etc.
        if (preg_match('/(?:hasOne|hasMany|belongsTo|belongsToMany|morphOne|morphMany|morphToMany|hasManyThrough)\s*\(\s*([A-Za-z\\\\]+)::class/', $methodBody, $matches)) {
            $modelClass = $matches[1];

            // Handle self::class or static::class
            if ($modelClass === 'self' || $modelClass === 'static') {
                return '\\'.$method->getDeclaringClass()->getName();
            }

            // If it's not an FQCN, try to resolve it from use statements
            if (! str_starts_with($modelClass, '\\')) {
                $resolvedClass = $this->resolveClassFromUseStatements($content, $modelClass);
                if ($resolvedClass) {
                    return '\\'.ltrim($resolvedClass, '\\');
                }

                // Fallback to namespace resolution
                $namespace = $method->getDeclaringClass()->getNamespaceName();
                $modelClass = $namespace.'\\'.$modelClass;
            }

            // Remove leading backslash for consistency
            return '\\'.ltrim($modelClass, '\\');
        }

        return null;
    }

    /**
     * Resolve class name from use statements in file.
     */
    private function resolveClassFromUseStatements(string $fileContent, string $className): ?string
    {
        // Extract use statements
        if (preg_match_all('/^use\s+([^;]+);/m', $fileContent, $matches)) {
            foreach ($matches[1] as $useStatement) {
                $useStatement = trim($useStatement);

                // Handle "use Foo\Bar as Baz"
                if (str_contains($useStatement, ' as ')) {
                    [$fqcn, $alias] = array_map(trim(...), explode(' as ', $useStatement));
                    if ($alias === $className) {
                        return $fqcn;
                    }
                } elseif (str_ends_with($useStatement, '\\'.$className) || $useStatement === $className) {
                    // Direct match
                    return $useStatement;
                } elseif (class_basename($useStatement) === $className) {
                    // Match by basename
                    return $useStatement;
                }
            }
        }

        return null;
    }

    /**
     * Detect all accessors in the model.
     *
     * @return array<string, string>
     */
    private function detectAccessors(ReflectionClass $reflection): array
    {
        $accessors = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $method) {
            $methodName = $method->getName();

            // Detect "get{Name}Attribute" pattern (Laravel 9 and below)
            if (preg_match('/^get([A-Z].+)Attribute$/', $methodName, $matches)) {
                $attributeName = Str::snake($matches[1]);
                $returnType = $this->getReturnTypeString($method) ?: 'mixed';
                $accessors[$attributeName] = $returnType;

                continue;
            }

            // Detect "Attribute" return type (Laravel 9+)
            $returnType = $method->getReturnType();
            if ($returnType instanceof ReflectionNamedType && $returnType->getName() === Attribute::class) {
                $accessors[Str::snake($methodName)] = 'mixed';
            }
        }

        return $accessors;
    }

    /**
     * Detect all scopes in the model.
     *
     * @return array<string, ReflectionMethod>
     */
    private function detectScopes(ReflectionClass $reflection): array
    {
        $scopes = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();

            // Detect "scope{Name}" pattern
            if (preg_match('/^scope([A-Z].+)$/', $methodName, $matches)) {
                $scopeName = Str::camel($matches[1]);
                $scopes[$scopeName] = $method;
            }
        }

        return $scopes;
    }

    /**
     * Get method parameters as a string.
     */
    private function getMethodParameters(ReflectionMethod $method): string
    {
        $params = [];

        foreach ($method->getParameters() as $param) {
            // Skip $query parameter for scopes
            if ($param->getName() === 'query') {
                continue;
            }

            $type = $param->getType();
            $typeString = $type instanceof ReflectionNamedType ? $type->getName().' ' : '';
            $default = $param->isDefaultValueAvailable() ? ' = '.var_export($param->getDefaultValue(), true) : '';

            $params[] = $typeString.'$'.$param->getName().$default;
        }

        return implode(', ', $params);
    }

    /**
     * Get return type as string.
     */
    private function getReturnTypeString(ReflectionMethod $method): ?string
    {
        $returnType = $method->getReturnType();

        if (! $returnType) {
            return null;
        }

        if ($returnType instanceof ReflectionNamedType) {
            $name = $returnType->getName();

            // Add leading backslash for class types
            if (! in_array($name, ['string', 'int', 'float', 'bool', 'array', 'object', 'void', 'mixed', 'null', 'never'])) {
                return '\\'.ltrim($name, '\\');
            }

            return $name;
        }

        return 'mixed';
    }

    /**
     * Write annotations and attributes to the model file.
     *
     * @param  array<int, string>  $annotations
     */
    private function writeAnnotations(ReflectionClass $reflection, array $annotations): void
    {
        $filePath = $reflection->getFileName();

        if (! $filePath) {
            return;
        }

        $lines = explode("\n", File::get($filePath));
        $classLine = $this->findClassLine($lines, $reflection->getShortName());

        if ($classLine === null) {
            return;
        }

        $this->removeOldMetadata($lines, $classLine);

        $builderFQCN = 'App\\Models\\QueryBuilders\\'.$reflection->getShortName().'QueryBuilder';

        $blocks = $this->prepareMetadataBlocks($reflection, $annotations, $builderFQCN);

        array_splice($lines, $classLine, 0, $blocks);

        $this->ensureImports($lines, $builderFQCN);

        File::put($filePath, implode("\n", $lines));
    }

    /**
     * Find the line number where the class is defined.
     */
    private function findClassLine(array $lines, string $shortName): ?int
    {
        return array_find_key($lines, function ($line) use ($shortName): int|false {
            return preg_match(
                '/^(final\s+)?(abstract\s+)?class\s+'.preg_quote($shortName, '/').'/',
                trim($line)
            );
        });
    }

    /**
     * Remove old docblocks and attributes from the model lines.
     */
    private function removeOldMetadata(array &$lines, int &$classLine): void
    {
        // 1. Remove existing #[UseEloquentBuilder]
        if (isset($lines[$classLine - 1]) && str_contains($lines[$classLine - 1], '#[UseEloquentBuilder')) {
            array_splice($lines, $classLine - 1, 1);
            $classLine--;
        }

        // 2. Remove existing PHPDoc block
        $docBlockStart = null;
        $docBlockEnd = null;

        for ($i = $classLine - 1; $i >= 0; $i--) {
            $trimmed = trim($lines[$i]);

            if ($trimmed === '*/') {
                $docBlockEnd = $i;
                for ($j = $i - 1; $j >= 0; $j--) {
                    if (str_starts_with(trim($lines[$j]), '/**')) {
                        $docBlockStart = $j;
                        break 2;
                    }
                }
            }

            if ($trimmed !== '' && ! str_starts_with($trimmed, '#') && ! str_starts_with($trimmed, '/*')) {
                break;
            }
        }

        if ($docBlockStart !== null && $docBlockEnd !== null) {
            array_splice($lines, $docBlockStart, $docBlockEnd - $docBlockStart + 1);
            $classLine -= ($docBlockEnd - $docBlockStart + 1);
        }
    }

    /**
     * Prepare the docblock and attribute blocks for the model.
     *
     * @param  array<int, string>  $annotations
     * @return array<string>
     */
    private function prepareMetadataBlocks(ReflectionClass $reflection, array $annotations, string $builderFQCN): array
    {
        $docBlock = $this->buildNewDocBlock($annotations);

        $builderShortName = class_basename($builderFQCN);
        $attribute = "#[UseEloquentBuilder({$builderShortName}::class)]";

        return array_merge($docBlock, [$attribute]);
    }

    /**
     * Build the new PHPDoc block from annotations.
     *
     * @param  array<int, string>  $annotations
     * @return array<string>
     */
    private function buildNewDocBlock(array $annotations): array
    {
        $docBlock = ['/**'];
        foreach ($annotations as $annotation) {
            $docBlock[] = " * {$annotation}";
        }
        $docBlock[] = ' */';

        return $docBlock;
    }

    /**
     * Ensure necessary imports exist in the model file.
     *
     * @param  array<string>  $lines
     */
    private function ensureImports(array &$lines, string $builderFQCN): void
    {
        $content = implode("\n", $lines);
        $newImports = [];

        if (! str_contains($content, 'use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;')) {
            $newImports[] = 'use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;';
        }
        if (! str_contains($content, "use {$builderFQCN};")) {
            $newImports[] = "use {$builderFQCN};";
        }

        if ($newImports === []) {
            return;
        }

        $lastUseIndex = -1;
        $namespaceIndex = -1;

        foreach ($lines as $index => $line) {
            if (str_starts_with(trim($line), 'use ') && ! str_contains($line, '{')) {
                $lastUseIndex = $index;
            }
            if (str_starts_with(trim($line), 'namespace ')) {
                $namespaceIndex = $index;
            }
            if (preg_match('/^(final\s+)?(abstract\s+)?class\s+/', trim($line))) {
                break;
            }
        }

        if ($lastUseIndex !== -1) {
            array_splice($lines, $lastUseIndex + 1, 0, $newImports);
        } elseif ($namespaceIndex !== -1) {
            array_splice($lines, $namespaceIndex + 1, 0, array_merge([''], $newImports));
        }
    }

    /**
     * Discover all models in app/Models directory.
     *
     * @return array<string>
     */
    private function discoverModels(): array
    {
        $modelsPath = app_path('Models');
        $models = [];

        if (! File::isDirectory($modelsPath)) {
            return [];
        }

        $files = File::allFiles($modelsPath);

        foreach ($files as $file) {
            $class = 'App\\Models\\'.str_replace(
                ['/', '.php'],
                ['\\', ''],
                Str::after($file->getPathname(), app_path('Models').DIRECTORY_SEPARATOR)
            );

            if (class_exists($class)) {
                $models[] = $class;
            }
        }

        return $models;
    }

    /**
     * Resolve model class from name.
     */
    private function resolveModelClass(string $name): string
    {
        if (class_exists($name)) {
            return $name;
        }

        $class = 'App\\Models\\'.$name;

        if (! class_exists($class)) {
            $this->error("Model {$name} not found");
            exit(1);
        }

        return $class;
    }

    /**
     * Check if class is a valid Eloquent model.
     */
    private function isValidModel(string $class): bool
    {
        if (! class_exists($class)) {
            return false;
        }

        return is_subclass_of($class, Model::class);
    }

    /**
     * Ensure the model has a QueryBuilder and the #[UseEloquentBuilder] attribute.
     */
    private function ensureQueryBuilder(ReflectionClass $reflection, bool $dryRun): ?string
    {
        // Check for #[UseEloquentBuilder] attribute
        $attributes = $reflection->getAttributes(UseEloquentBuilder::class);

        if ($attributes !== []) {
            $attribute = $attributes[0];
            $args = $attribute->getArguments();

            return $args[0] ?? null;
        }

        // If skip rule applies (phrasing "#annotation" as the attribute itself)
        // or if we're just checking existence.

        $modelFQCN = $reflection->getName();
        $modelShortName = $reflection->getShortName();
        $builderName = $modelShortName.'QueryBuilder';
        $builderFQCN = "App\\Models\\QueryBuilders\\{$builderName}";
        $builderPath = app_path("Models/QueryBuilders/{$builderName}.php");

        if ($dryRun) {
            $this->info("  [Dry Run] Would create builder: {$builderFQCN}");
            $this->info("  [Dry Run] Would add #[UseEloquentBuilder] to {$modelShortName}");

            return $builderFQCN;
        }

        // Create builder file if it doesn't exist
        if (! File::exists($builderPath)) {
            $this->createBuilderFile($builderPath, $builderName, $modelFQCN);
        }

        // Add attribute to model file
        $this->addAttributeToModel($reflection, $builderFQCN);

        return $builderFQCN;
    }

    /**
     * Create a new QueryBuilder class file.
     */
    private function createBuilderFile(string $path, string $builderName, string $modelFQCN): void
    {
        $modelShortName = class_basename($modelFQCN);
        $namespace = 'App\\Models\\QueryBuilders';

        $content = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use {$modelFQCN};
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<{$modelShortName}>
 */
class {$builderName} extends Builder
{
}

PHP;

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);
        $this->comment("  ✓ Created QueryBuilder: {$builderName}");
    }

    /**
     * Add the #[UseEloquentBuilder] attribute and necessary imports to the model.
     */
    private function addAttributeToModel(ReflectionClass $reflection, string $builderFQCN): void
    {
        $filePath = $reflection->getFileName();
        if (! $filePath) {
            return;
        }

        $content = File::get($filePath);
        $lines = explode("\n", $content);

        // Check if attribute already exists in text
        if (str_contains($content, '#[UseEloquentBuilder')) {
            return;
        }

        $builderShortName = class_basename($builderFQCN);
        $attribute = "#[UseEloquentBuilder({$builderShortName}::class)]";

        // Collect missing imports
        $newImports = [];
        if (! str_contains($content, 'use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;')) {
            $newImports[] = 'use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;';
        }
        if (! str_contains($content, "use {$builderFQCN};")) {
            $newImports[] = "use {$builderFQCN};";
        }

        if ($newImports !== []) {
            // Find the last global 'use' statement (before the class declaration)
            $lastGlobalUseIndex = -1;
            $classLineFinder = -1;
            foreach ($lines as $index => $line) {
                if (preg_match('/^(final\s+)?(abstract\s+)?class\s+/', trim($line))) {
                    $classLineFinder = $index;
                    break;
                }
                if (str_starts_with(trim($line), 'use ') && ! str_contains($line, '{')) {
                    $lastGlobalUseIndex = $index;
                }
            }

            if ($lastGlobalUseIndex !== -1) {
                array_splice($lines, $lastGlobalUseIndex + 1, 0, $newImports);
            } elseif ($classLineFinder !== -1) {
                // If no use statements found, insert after namespace
                foreach ($lines as $index => $line) {
                    if (str_starts_with(trim($line), 'namespace ')) {
                        array_splice($lines, $index + 1, 0, array_merge([''], $newImports));
                        break;
                    }
                }
            }
        }
        $classLine = array_find_key($lines, fn ($line): int|false => preg_match('/^(final\s+)?(abstract\s+)?class\s+'.preg_quote($reflection->getShortName(), '/').'/', trim($line)));

        if ($classLine !== null) {
            // Attribute should be right above the class line.
            // PHP allows attributes before or after docblocks, but standard Laravel usage
            // often prefers it right before the class.
            array_splice($lines, $classLine, 0, [$attribute]);
        }

        File::put($filePath, implode("\n", $lines));
        $this->comment("  ✓ Added #[UseEloquentBuilder] to {$reflection->getShortName()}");
    }

    /**
     * Ensure the model has an Entity class.
     */
    private function ensureEntity(ReflectionClass $reflection, array $columns, array $casts, bool $dryRun): void
    {
        $modelShortName = $reflection->getShortName();
        $entityName = $modelShortName.'Entity';
        $entityFQCN = "App\\Models\\Entities\\{$entityName}";
        $entityPath = app_path("Models/Entities/{$entityName}.php");

        if ($dryRun) {
            if (! File::exists($entityPath)) {
                $this->info("  [Dry Run] Would create entity: {$entityFQCN}");
            } else {
                $this->info("  [Dry Run] Entity already exists: {$entityFQCN}");
            }

            return;
        }

        // Create entity file if it doesn't exist
        if (! File::exists($entityPath)) {
            $this->createEntityFile($entityPath, $entityName, $columns, $casts);
        }
    }

    /**
     * Create a new Entity class file.
     */
    private function createEntityFile(string $path, string $entityName, array $columns, array $casts): void
    {
        $namespace = 'App\\Models\\Entities';
        $properties = [];
        $imports = [
            'use WireNinja\\Prasmanan\\Traits\\BaseEntity;',
        ];
        $importedClasses = [];

        foreach ($columns as $column) {
            $name = $column['name'];
            $type = $this->mapDatabaseTypeToPhp($column, $casts[$name] ?? null);
            $nullable = $column['nullable'] ? '?' : '';

            // Track imports for special types
            if (str_starts_with($type, '\\')) {
                $cleanType = ltrim($type, '\\');
                if (! in_array($cleanType, $importedClasses)) {
                    $imports[] = "use {$cleanType};";
                    $importedClasses[] = $cleanType;
                }
                $type = class_basename($cleanType);
            }

            $properties[] = "        public {$nullable}{$type} \${$name},";
        }

        // Remove trailing comma from last property
        if ($properties !== []) {
            $properties[count($properties) - 1] = rtrim($properties[count($properties) - 1], ',');
        }

        $importsString = implode("\n", $imports);
        $propertiesString = implode("\n", $properties);

        $content = <<<PHP
<?php

namespace {$namespace};

{$importsString}

final class {$entityName}
{
    use BaseEntity;

    public function __construct(
{$propertiesString}
    ) {}
}

PHP;

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);
        $this->comment("  ✓ Created Entity: {$entityName}");
    }

    /**
     * Ensure the model has an Optional Entity class.
     */
    private function ensureOptionalEntity(ReflectionClass $reflection, array $columns, array $casts, bool $dryRun): void
    {
        $modelShortName = $reflection->getShortName();
        $entityName = $modelShortName.'OptionalEntity';
        $entityFQCN = "App\\Models\\OptionalEntities\\{$entityName}";
        $entityPath = app_path("Models/OptionalEntities/{$entityName}.php");

        if ($dryRun) {
            if (! File::exists($entityPath)) {
                $this->info("  [Dry Run] Would create optional entity: {$entityFQCN}");
            } else {
                $this->info("  [Dry Run] Optional entity already exists: {$entityFQCN}");
            }

            return;
        }

        // Create optional entity file if it doesn't exist
        if (! File::exists($entityPath)) {
            $this->createOptionalEntityFile($entityPath, $entityName, $columns, $casts);
        }
    }

    /**
     * Create a new Optional Entity class file where all fields are nullable.
     */
    private function createOptionalEntityFile(string $path, string $entityName, array $columns, array $casts): void
    {
        $namespace = 'App\\Models\\OptionalEntities';
        $properties = [];
        $imports = [
            'use WireNinja\\Prasmanan\\Traits\\BaseEntity;',
        ];
        $importedClasses = [];

        foreach ($columns as $column) {
            $name = $column['name'];
            $type = $this->mapDatabaseTypeToPhp($column, $casts[$name] ?? null);
            // All fields are nullable in optional entity
            $nullable = '?';

            // Track imports for special types
            if (str_starts_with($type, '\\')) {
                $cleanType = ltrim($type, '\\');
                if (! in_array($cleanType, $importedClasses)) {
                    $imports[] = "use {$cleanType};";
                    $importedClasses[] = $cleanType;
                }
                $type = class_basename($cleanType);
            }

            $properties[] = "        public {$nullable}{$type} \${$name} = null,";
        }

        // Remove trailing comma from last property
        if ($properties !== []) {
            $properties[count($properties) - 1] = rtrim($properties[count($properties) - 1], ',');
        }

        $importsString = implode("\n", $imports);
        $propertiesString = implode("\n", $properties);

        $content = <<<PHP
<?php

namespace {$namespace};

{$importsString}

final class {$entityName}
{
    use BaseEntity;

    public function __construct(
{$propertiesString}
    ) {}
}

PHP;

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);
        $this->comment("  ✓ Created Optional Entity: {$entityName}");
    }
}
