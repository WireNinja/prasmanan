<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Libs;

use BackedEnum;
use Brick\Math\BigDecimal;
use InvalidArgumentException;
use JsonException;
use Stringable;
use Throwable;
use Traversable;
use UnitEnum;

final class TypeNormalizer
{
    public static function int(mixed $value): ?int
    {
        if (self::isNullValue($value)) {
            return null;
        }

        return self::castInt($value);
    }

    /**
     * @throws Throwable
     */
    public static function mustInt(mixed $value): int
    {
        throw_if(self::isNullValue($value), InvalidArgumentException::class, 'Value cannot be null when casting to int.');

        return self::castInt($value);
    }

    public static function float(mixed $value): ?float
    {
        if (self::isNullValue($value)) {
            return null;
        }

        return self::castFloat($value);
    }

    /**
     * @throws Throwable
     */
    public static function mustFloat(mixed $value): float
    {
        throw_if(self::isNullValue($value), InvalidArgumentException::class, 'Value cannot be null when casting to float.');

        return self::castFloat($value);
    }

    public static function bool(mixed $value): ?bool
    {
        if (self::isNullValue($value)) {
            return null;
        }

        return self::castBool($value);
    }

    /**
     * @throws Throwable
     */
    public static function mustBool(mixed $value): bool
    {
        throw_if(self::isNullValue($value), InvalidArgumentException::class, 'Value cannot be null when casting to bool.');

        return self::castBool($value);
    }

    public static function string(mixed $value): ?string
    {
        if (self::isNullValue($value)) {
            return null;
        }

        return self::castString($value);
    }

    /**
     * @throws Throwable
     */
    public static function mustString(mixed $value): string
    {
        throw_if(self::isNullValue($value), InvalidArgumentException::class, 'Value cannot be null when casting to string.');

        return self::castString($value);
    }

    /**
     * @return array<int|string, mixed>|null
     */
    public static function array(mixed $value): ?array
    {
        if (self::isNullValue($value)) {
            return null;
        }

        return self::castArray($value);
    }

    /**
     * @return array<int|string, mixed>
     *
     * @throws Throwable
     */
    public static function mustArray(mixed $value): array
    {
        throw_if(self::isNullValue($value), InvalidArgumentException::class, 'Value cannot be null when casting to array.');

        return self::castArray($value);
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $class
     * @return T|null
     */
    public static function model(string $class, mixed $value): ?object
    {
        if (self::isNullValue($value)) {
            return null;
        }

        return self::castModel($class, $value);
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $class
     * @return T
     *
     * @throws Throwable
     */
    public static function mustModel(string $class, mixed $value): object
    {
        throw_if(self::isNullValue($value), InvalidArgumentException::class, 'Value cannot be null when casting to model.');

        return self::castModel($class, $value);
    }

    /**
     * @template TEnum of UnitEnum
     *
     * @param  class-string<TEnum>  $enumClass
     * @return TEnum|null
     */
    public static function enum(string $enumClass, mixed $value): ?UnitEnum
    {
        if (self::isNullValue($value)) {
            return null;
        }

        return self::castEnum($enumClass, $value);
    }

    /**
     * @template TEnum of UnitEnum
     *
     * @param  class-string<TEnum>  $enumClass
     * @return TEnum
     *
     * @throws Throwable
     */
    public static function mustEnum(string $enumClass, mixed $value): UnitEnum
    {
        throw_if(self::isNullValue($value), InvalidArgumentException::class, 'Value cannot be null when casting to enum.');

        return self::castEnum($enumClass, $value);
    }

    private static function castInt(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_string($value)) {
            $value = mb_trim($value);
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        throw new InvalidArgumentException('Value cannot be normalized to int.');
    }

    private static function castFloat(mixed $value): float
    {
        if (is_float($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return $value ? 1.0 : 0.0;
        }

        if (is_string($value)) {
            return BigDecimal::of($value)->toFloat();
        }

        if (is_numeric($value)) {
            return BigDecimal::of($value)->toFloat();
        }

        throw new InvalidArgumentException('Value cannot be normalized to float.');
    }

    private static function castBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return $value !== 0;
        }

        if (is_string($value)) {
            $value = mb_strtolower(mb_trim($value));

            return match ($value) {
                '1', 'true', 'yes', 'on' => true,
                '0', 'false', 'no', 'off', '' => false,
                default => throw new InvalidArgumentException('Value cannot be normalized to bool.'),
            };
        }

        throw new InvalidArgumentException('Value cannot be normalized to bool.');
    }

    private static function castString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        throw new InvalidArgumentException('Value cannot be normalized to string.');
    }

    /**
     * @return array<int|string, mixed>
     */
    private static function castArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($value instanceof Traversable) {
            return iterator_to_array($value);
        }

        if (is_string($value)) {
            $json = mb_trim($value);

            if ($json === '') {
                return [];
            }

            try {
                $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                throw new InvalidArgumentException('String cannot be normalized to array.');
            }

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new InvalidArgumentException('Value cannot be normalized to array.');
    }

    private static function isNullValue(mixed $value): bool
    {
        return $value === null;
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $class
     * @return T
     *
     * @throws InvalidArgumentException
     */
    private static function castModel(string $class, mixed $value): object
    {
        if (! is_object($value)) {
            throw new InvalidArgumentException(sprintf('Value must be an object, %s given.', gettype($value)));
        }

        if (! $value instanceof $class) {
            throw new InvalidArgumentException(sprintf('Value must be an instance of %s, %s given.', $class, $value::class));
        }

        return $value;
    }

    /**
     * @template TEnum of UnitEnum
     *
     * @param  class-string<TEnum>  $enumClass
     * @return TEnum
     */
    private static function castEnum(string $enumClass, mixed $value): UnitEnum
    {
        if (! enum_exists($enumClass)) {
            throw new InvalidArgumentException(sprintf('Class %s is not a valid enum.', $enumClass));
        }

        if ($value instanceof $enumClass) {
            /** @var TEnum $value */
            return $value;
        }

        if (is_subclass_of($enumClass, BackedEnum::class) && (is_string($value) || is_int($value))) {
            /** @var class-string<TEnum&BackedEnum> $enumClass */
            $case = $enumClass::tryFrom($value);
            if ($case instanceof UnitEnum) {
                /** @var TEnum $case */
                return $case;
            }
        }

        if (is_string($value)) {
            $needle = mb_trim($value);

            foreach ($enumClass::cases() as $case) {
                if ($case->name === $needle) {
                    /** @var TEnum $case */
                    return $case;
                }
            }
        }

        throw new InvalidArgumentException(sprintf('Value cannot be normalized to enum %s.', $enumClass));
    }
}
