<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Exceptions\Monitoring;

use RuntimeException;

/**
 * Exception ini dilempar sebagai sinyal ke Nightwatch ketika
 * mengakses atribut model yang belum dideklarasikan di select atau appends.
 */
final class MissingAttributeViolationException extends RuntimeException
{
    public static function create(string $modelClass, string $attribute): self
    {
        $message = sprintf(
            '[MissingAttributeViolation] %s | attribute:%s',
            class_basename($modelClass),
            $attribute
        );

        return new self($message);
    }
}
