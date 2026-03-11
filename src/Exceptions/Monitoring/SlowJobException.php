<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Exceptions\Monitoring;

use RuntimeException;

/**
 * Exception ini dilempar ketika proses Queue Job memakan waktu terlalu lama.
 * Threshold: > 10000ms.
 * Exception ini berfungsi sebagai sinyal monitoring Nightwatch dan dilempar via rescue().
 */
final class SlowJobException extends RuntimeException
{
    public static function create(string $jobName, float|int $duration, string $queue, ?string $connection): self
    {
        $message = sprintf(
            '[SlowJob] %s | %sms | queue:%s | connection:%s',
            $jobName,
            $duration,
            $queue,
            $connection ?? 'default'
        );

        return new self($message);
    }
}
