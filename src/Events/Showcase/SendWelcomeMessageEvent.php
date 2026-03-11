<?php

declare(strict_types=1);

namespace WireNinja\Prasmanan\Events\Showcase;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use WireNinja\Prasmanan\Broadcasting\PrasmananBroadcast;

class SendWelcomeMessageEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $message,
        public int $userId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(PrasmananBroadcast::userChannel($this->userId)),
        ];
    }
}
