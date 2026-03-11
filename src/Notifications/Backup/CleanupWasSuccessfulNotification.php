<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Notifications\Backup;

use NotificationChannels\Telegram\TelegramMessage;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification as BaseNotification;

class CleanupWasSuccessfulNotification extends BaseNotification
{
    public function toTelegram($notifiable)
    {
        return TelegramMessage::create()
            ->to((string) config('services.telegram-bot-api.chat_id'))
            ->content("🧹 *Cleanup Successful*\n"
                ."Application: `{$this->applicationName()}`\n"
                ."Disk: `{$this->diskName()}`\n"
                .'Old backups cleared.');
    }
}
