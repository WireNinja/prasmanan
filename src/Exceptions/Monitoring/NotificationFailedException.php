<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Exceptions\Monitoring;

use RuntimeException;

/**
 * Exception ini dilempar sebagai sinyal kegagalan komunikasi/notifikasi
 * eksternal seperti Email, SMS, dsb.
 * Membantu merekam log Nightwatch tanpa mengganggu alur sistem notifikasi yang berjalan asinkron.
 * Disesuaikan dari Laravel MessageFailed/NotificationFailed bindings.
 */
final class NotificationFailedException extends RuntimeException
{
    public static function create(string $notificationClass, string $channel, string $notifiableClass, string|int|null $notifiableId): self
    {
        $message = sprintf(
            '[NotificationFailed] %s | channel:%s | notifiable:%s | id:%s',
            class_basename($notificationClass),
            $channel,
            class_basename($notifiableClass),
            $notifiableId ?? 'unknown'
        );

        return new self($message);
    }
}
