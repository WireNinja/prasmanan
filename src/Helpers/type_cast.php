<?php

declare(strict_types=1);

use WireNinja\Prasmanan\Libs\TypeNormalizer;

if (! function_exists('to_int')) {
    function to_int(mixed $value): ?int
    {
        return TypeNormalizer::int($value);
    }
}

if (! function_exists('to_int_strict')) {
    function to_int_strict(mixed $value): int
    {
        return TypeNormalizer::mustInt($value);
    }
}

if (! function_exists('to_float')) {
    function to_float(mixed $value): ?float
    {
        return TypeNormalizer::float($value);
    }
}

if (! function_exists('to_float_strict')) {
    function to_float_strict(mixed $value): float
    {
        return TypeNormalizer::mustFloat($value);
    }
}

if (! function_exists('to_bool')) {
    function to_bool(mixed $value): ?bool
    {
        return TypeNormalizer::bool($value);
    }
}

if (! function_exists('to_bool_strict')) {
    function to_bool_strict(mixed $value): bool
    {
        return TypeNormalizer::mustBool($value);
    }
}

if (! function_exists('to_string')) {
    function to_string(mixed $value): ?string
    {
        return TypeNormalizer::string($value);
    }
}

if (! function_exists('to_string_strict')) {
    function to_string_strict(mixed $value): string
    {
        return TypeNormalizer::mustString($value);
    }
}

if (! function_exists('to_array')) {
    /**
     * @return array<int|string, mixed>|null
     */
    function to_array(mixed $value): ?array
    {
        return TypeNormalizer::array($value);
    }
}

if (! function_exists('to_array_strict')) {
    /**
     * @return array<int|string, mixed>
     */
    function to_array_strict(mixed $value): array
    {
        return TypeNormalizer::mustArray($value);
    }
}

if (! function_exists('to_model')) {
    /**
     * @template T of object
     *
     * @param  class-string<T>  $class
     * @return T|null
     */
    function to_model(string $class, mixed $value): ?object
    {
        return TypeNormalizer::model($class, $value);
    }
}

if (! function_exists('to_model_strict')) {
    /**
     * @template T of object
     *
     * @param  class-string<T>  $class
     * @return T
     */
    function to_model_strict(string $class, mixed $value): object
    {
        return TypeNormalizer::mustModel($class, $value);
    }
}

if (! function_exists('to_enum')) {
    /**
     * @template TEnum of UnitEnum
     *
     * @param  class-string<TEnum>  $enumClass
     * @return TEnum|null
     */
    function to_enum(string $enumClass, mixed $value): ?UnitEnum
    {
        return TypeNormalizer::enum($enumClass, $value);
    }
}

if (! function_exists('to_enum_strict')) {
    /**
     * @template TEnum of UnitEnum
     *
     * @param  class-string<TEnum>  $enumClass
     * @return TEnum
     */
    function to_enum_strict(string $enumClass, mixed $value): UnitEnum
    {
        return TypeNormalizer::mustEnum($enumClass, $value);
    }
}

if (! function_exists('enum_cast')) {
    /**
     * @template TEnum of UnitEnum
     *
     * @param  class-string<TEnum>  $enumClass
     * @return TEnum
     */
    function enum_cast(string $enumClass, mixed $value): UnitEnum
    {
        return to_enum_strict($enumClass, $value);
    }
}
