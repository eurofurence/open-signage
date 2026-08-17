<?php

namespace App\Events;

use App\Models\Announcement;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class UpdateAnnouncementEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(public readonly Announcement $announcement, public readonly string $action) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('ScreenAll'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'announcement.' . $this->action;
    }

    public function broadcastWith(): array
    {
        return $this->announcement->toArray();
    }
}
