<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Notifications\Backup;

use NotificationChannels\Telegram\TelegramMessage;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification as BaseNotification;

class BackupWasSuccessfulNotification extends BaseNotification
{
    public function toTelegram($notifiable)
    {
        return TelegramMessage::create()
            ->to((string) config('services.telegram-bot-api.chat_id'))
            ->content("✅ *Backup Successful!*\n"
                ."Application: `{$this->applicationName()}`\n"
                ."Disk: `{$this->diskName()}`\n"
                .'New backup saved nicely.');
    }
}
