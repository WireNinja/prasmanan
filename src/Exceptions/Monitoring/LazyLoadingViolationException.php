<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Exceptions\Monitoring;

use RuntimeException;

/**
 * Exception ini dilempar ketika terdeteksi masalah N+1 query akibat
 * lazy loading yang tidak disengaja di tahap production.
 * Berfungsi sebagai sinyal ke Nightwatch dan tidak akan throw di production jika dibungkus rescue().
 * Threshold: Segera setelah terjadi di strict mode production.
 */
final class LazyLoadingViolationException extends RuntimeException
{
    public static function create(string $modelClass, string $relation): self
    {
        $message = sprintf(
            '[LazyLoadingViolation] %s | relation:%s',
            class_basename($modelClass),
            $relation
        );

        return new self($message);
    }
}
