<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Notifications\Showcase;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;
use WireNinja\Prasmanan\Supports\PrasmananConstants;

class SendWelcomeNotification extends Notification
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Approved!')
            ->icon(PrasmananConstants::PWA_ICON_192)
            ->badge(PrasmananConstants::PWA_BADGE_64)
            ->body('Your account was approved!')
            ->action('View account', 'view_account')
            ->options(['TTL' => 1000])
            ->renotify()
            ->requireInteraction()
            ->tag('test')
            ->vibrate([200, 100, 200]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
