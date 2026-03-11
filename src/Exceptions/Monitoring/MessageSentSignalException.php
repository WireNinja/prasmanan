<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Exceptions\Monitoring;

use RuntimeException;

/**
 * Exception/Sinyal yang dilempar saat email BERHASIL terkirim.
 * Dipakai sebagai Audit Trail di Nightwatch log pipeline.
 */
final class MessageSentSignalException extends RuntimeException
{
    public static function create(array $to, string $subject): self
    {
        $message = sprintf(
            '[MessageSentAudit] subject:%s | to:%s',
            $subject,
            implode(', ', $to)
        );

        return new self($message);
    }
}
