<?php

namespace App\Events;

use App\Models\Artwork;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateArtworkEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly Artwork $artwork, public readonly string $action)
    {
    }

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
        return $this->artwork->toArray();
    }
}
