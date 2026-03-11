<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Exceptions\Monitoring;

use RuntimeException;

/**
 * Exception ini dilempar sebagai sinyal ke Nightwatch ketika
 * job queue secara resmi mengalami kegagalan (JobFailed).
 */
final class QueueJobFailedSignalException extends RuntimeException
{
    public static function create(string $jobName, string $queue, ?string $connection, string $originalErrorMessage): self
    {
        $message = sprintf(
            '[QueueJobFailed] %s | queue:%s | connection:%s | error:%s',
            $jobName,
            $queue,
            $connection ?? 'default',
            $originalErrorMessage
        );

        return new self($message);
    }
}
