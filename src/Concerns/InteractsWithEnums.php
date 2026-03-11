<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Concerns;

use BackedEnum;
use BadMethodCallException;
use Illuminate\Support\Collection;

/**
 * Trait for providing universal utilities to Enums.
 *
 * This trait merges basic array conversion (for UI), simple conditional checks (is/in),
 * and magic method helpers (isDraft/isNotDraft).
 */
trait InteractsWithEnums
{
    // =========================================================================
    // SECTION: STATIC ARRAY & COLLECTION UTILITIES (For UI Select/Options)
    // =========================================================================

    /**
     * Get an array of all case names.
     *
     * @return array<int, string>
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * Get an array of all case values.
     *
     * @return array<int, int|string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get an associative array of value => label for select inputs.
     *
     * @return array<int|string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            // @phpstan-ignore-next-line
            $options[$case->value] = method_exists($case, 'getLabel') ? $case->getLabel() : $case->name;
        }

        return $options;
    }

    /**
     * Get an array of all case labels.
     *
     * @return array<int, string>
     */
    public static function labels(): array
    {
        return array_values(self::options());
    }

    /**
     * Pick a random enum case (ideal for seeders and tests).
     */
    public static function random(): self
    {
        $cases = self::cases();

        return $cases[array_rand($cases)];
    }

    /**
     * Transform all enum cases into a Laravel Collection.
     *
     * @return Collection<int, self>
     */
    public static function toCollection(): Collection
    {
        // @phpstan-ignore-next-line
        return collect(self::cases());
    }

    // =========================================================================
    // SECTION: INSTANCE COMPARISON UTILITIES (For Business Logic)
    // =========================================================================

    /**
     * Check if the current case is equal to the given target (value or Enum case).
     */
    public function is(mixed $value): bool
    {
        if ($value instanceof BackedEnum) {
            return $this === $value;
        }

        return $this->value === $value;
    }

    /**
     * Check if the current case is NOT equal to the given target.
     */
    public function isNot(mixed $value): bool
    {
        return ! $this->is($value);
    }

    /**
     * Check if the current case exists in a list of targets.
     *
     * @param  array<int, mixed>  $values
     */
    public function in(array $values): bool
    {
        return array_any($values, fn ($value) => $this->is($value));
    }

    /**
     * Check if the current case does NOT exist in a list of targets.
     *
     * @param  array<int, mixed>  $values
     */
    public function notIn(array $values): bool
    {
        return ! $this->in($values);
    }

    // =========================================================================
    // SECTION: MAGIC METHOD HELPERS (Syntactic Sugar)
    // =========================================================================

    /**
     * Provide magic methods for easy checks.
     * Supported patterns:
     * - is{CaseName}() : bool
     * - isNot{CaseName}() : bool
     * - canTransitionTo{CaseName}() : bool (If HasFlow is used)
     *
     * @param  array<int, mixed>  $arguments
     *
     * @throws BadMethodCallException
     */
    public function __call(string $name, array $arguments): bool
    {
        // Pattern: is{Case} -> isDraft()
        if (str_starts_with($name, 'is') && ! str_starts_with($name, 'isNot') && strlen($name) > 2) {
            $caseName = substr($name, 2);
            foreach (self::cases() as $case) {
                if ($case->name === $caseName) {
                    return $this === $case;
                }
            }
        }

        // Pattern: isNot{Case} -> isNotDraft()
        if (str_starts_with($name, 'isNot') && strlen($name) > 5) {
            $caseName = substr($name, 5);
            foreach (self::cases() as $case) {
                if ($case->name === $caseName) {
                    return $this !== $case;
                }
            }
        }

        // Pattern: canTransitionTo{Case} -> canTransitionToSent()
        if (str_starts_with($name, 'canTransitionTo') && strlen($name) > 15) {
            $caseName = substr($name, 15);
            foreach (self::cases() as $case) {
                // Check if the enum also uses the 'HasFlow' trait
                // @phpstan-ignore-next-line
                if ($case->name === $caseName && method_exists($this, 'canTransitionTo')) {
                    return $this->canTransitionTo($case);
                }
            }
        }

        throw new BadMethodCallException(sprintf(
            'Method %s::%s() does not exist.',
            static::class,
            $name
        ));
    }
}
