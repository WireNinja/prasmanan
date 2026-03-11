<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Exceptions\Monitoring;

use RuntimeException;

/**
 * Exception yang dilempar saat query SQL memakan waktu melebihi batas (mis: > 1000ms).
 * Berfungsi sebagai sinyal asinkron untuk Nightwatch.
 * Jika ditangkap via rescue(), Exception ini tidak akan menyebabkan fail/HTTP 500 ke end user.
 */
final class SlowQueryException extends RuntimeException
{
    public static function create(string $sql, float|int $duration, ?string $connection): self
    {
        // Potong SQL agar message tidak bloated jika query-nya sangat panjang
        $shortSql = strlen($sql) > 100 ? substr($sql, 0, 97).'...' : $sql;

        $message = sprintf(
            '[SlowQuery] %s | %sms | connection:%s',
            $shortSql,
            $duration,
            $connection ?? 'default'
        );

        return new self($message);
    }
}
