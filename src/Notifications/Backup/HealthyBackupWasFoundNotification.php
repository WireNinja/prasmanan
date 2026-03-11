<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Notifications\Backup;

use NotificationChannels\Telegram\TelegramMessage;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification as BaseNotification;

class HealthyBackupWasFoundNotification extends BaseNotification
{
    public function toTelegram($notifiable)
    {
        return TelegramMessage::create()
            ->to((string) config('services.telegram-bot-api.chat_id'))
            ->content("🟢 *Healthy Backup Found*\n"
                ."Application: `{$this->applicationName()}`\n"
                ."Disk: `{$this->diskName()}`\n"
                .'The backups are considered healthy.');
    }
}
