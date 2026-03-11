<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Console\Commands\Showcase;

use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use WireNinja\Prasmanan\Events\Showcase\SendWelcomeMessageEvent;
use WireNinja\Prasmanan\Models\BaseUser;
use WireNinja\Prasmanan\Notifications\Showcase\SendWelcomeNotification;

class SendDummyBroadcast extends Command
{
    protected $signature = 'showcase:send-dummy-broadcast {user_id} {message?}';

    protected $description = 'Send a test broadcast and notification to a specific user';

    public function handle(): void
    {
        $user = $this->findTargetUser();

        if (! $user) {
            return;
        }

        $message = $this->argument('message') ?? 'This is a test welcome message at '.now();

        $this->sendNotifications($user, $message);

        $this->info("Broadcast sent successfully to user {$user->name} (ID: {$user->id})");
    }

    /**
     * Find the target user from the provided argument.
     */
    private function findTargetUser(): ?object
    {
        $userId = $this->argument('user_id');
        $userModelClass = config('auth.providers.users.model', BaseUser::class);
        $user = $userModelClass::query()->find($userId);

        if (! $user) {
            $this->error("User with ID {$userId} not found.");

            return null;
        }

        return $user;
    }

    /**
     * Send all types of dummy notifications to the user.
     */
    private function sendNotifications(object $user, string $message): void
    {
        // 1. Broadcast via Event
        event(new SendWelcomeMessageEvent($message, (int) $user->id));

        // 2. WebPush via Notification
        $user->notify(new SendWelcomeNotification);

        // 3. Filament UI Notification (Database & Broadcast)
        $user->notify(Notification::make()
            ->title('Test Notification')
            ->body('This is a test notification at '.now())
            ->success()
            ->sendToDatabase($user)
            ->toBroadcast());
    }
}
