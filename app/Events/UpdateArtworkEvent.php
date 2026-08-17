<?php

namespace App\Events;

use App\Models\Artwork;
use App\Services\ScreenDataGenerator;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class UpdateArtworkEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(public readonly Artwork $artwork, public readonly string $action) {}

    public function broadcastOn()
    {
        return [
            new Channel('ScreenAll'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'artwork.' . $this->action;
    }

    public function broadcastWith(): array
    {
        return ScreenDataGenerator::artwork($this->artwork);
    }
}
