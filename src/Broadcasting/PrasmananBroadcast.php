<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Broadcasting;

use Illuminate\Support\Facades\Broadcast;

class PrasmananBroadcast
{
    /**
     * Get the private channel name for a user.
     */
    public static function userChannel(int|string $id): string
    {
        return 'App.Models.User.'.$id;
    }

    /**
     * Register all core broadcasting channels for Prasmanan.
     */
    public static function all(): void
    {
        Broadcast::channel('App.Models.User.{id}', function ($user, $id): bool {
            return strval($user?->id) === strval($id);
        });
    }
}
