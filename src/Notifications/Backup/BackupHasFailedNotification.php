<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Notifications\Backup;

use NotificationChannels\Telegram\TelegramMessage;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification as BaseNotification;

class BackupHasFailedNotification extends BaseNotification
{
    public function toTelegram($notifiable)
    {
        $message = "🚨 *Backup Failed*\n"
            ."Application: `{$this->applicationName()}`\n"
            ."Disk: `{$this->diskName()}`\n";

        if ($this->event->exception) {
            $message .= "Exception message:\n`{$this->event->exception->getMessage()}`";
        }

        return TelegramMessage::create()
            ->to((string) config('services.telegram-bot-api.chat_id'))
            ->content($message);
    }
}
