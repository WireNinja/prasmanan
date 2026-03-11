<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Exceptions\Monitoring;

use RuntimeException;

/**
 * Exception ini dilempar sebagai sinyal ke Nightwatch ketika
 * ada penugasan ke atribut model yang diblok (discarded attribute).
 */
final class DiscardedAttributeViolationException extends RuntimeException
{
    public static function create(string $modelClass, array $attributes): self
    {
        $message = sprintf(
            '[DiscardedAttributeViolation] %s | attributes:%s',
            class_basename($modelClass),
            implode(', ', $attributes)
        );

        return new self($message);
    }
}
