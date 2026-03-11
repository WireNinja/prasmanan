<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Exceptions\Monitoring;

use RuntimeException;

/**
 * Exception/Sinyal yang dilempar saat terjadi percobaan pengiriman email.
 * Dipakai sebagai Audit Trail di Nightwatch, BUKAN sebagai error.
 */
final class MessageSendingSignalException extends RuntimeException
{
    public static function create(array $to, string $subject, string $mailer): self
    {
        $message = sprintf(
            '[MessageSendingAudit] subject:%s | to:%s | mailer:%s',
            $subject,
            implode(', ', $to),
            $mailer
        );

        return new self($message);
    }
}
