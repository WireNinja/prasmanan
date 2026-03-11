<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Exceptions\Monitoring;

use RuntimeException;

/**
 * Exception ini dilempar ketika HTTP request memakan waktu lebih dari threshold yang ditentukan.
 * Threshold: > 2000ms.
 * Exception ini adalah sinyal monitoring untuk Nightwatch dan dilempar via rescue().
 */
final class SlowRequestException extends RuntimeException
{
    public static function create(string $method, string $url, float|int $duration, int|string|null $userId, ?string $ip): self
    {
        $message = sprintf(
            '[SlowRequest] %s %s | %sms | user:%s | ip:%s',
            strtoupper($method),
            $url,
            $duration,
            $userId ?? 'guest',
            $ip ?? 'unknown'
        );

        return new self($message);
    }
}
