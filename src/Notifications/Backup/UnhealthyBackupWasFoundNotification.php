<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Notifications\Backup;

use NotificationChannels\Telegram\TelegramMessage;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification as BaseNotification;

class UnhealthyBackupWasFoundNotification extends BaseNotification
{
    public function toTelegram($notifiable)
    {
        $problem = collect($this->event->failureMessages ?? [])
            ->map(fn (array $f) => "[{$f['check']}] {$f['message']}")
            ->implode("\n");

        return TelegramMessage::create()
            ->to((string) config('services.telegram-bot-api.chat_id'))
            ->content("💔 *Unhealthy Backup Found*\n"
                ."Application: `{$this->applicationName()}`\n"
                ."Disk: `{$this->diskName()}`\n"
                ."Problem: `{$problem}`");
    }
}
