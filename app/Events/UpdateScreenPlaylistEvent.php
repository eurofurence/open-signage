<?php

namespace App\Events;

use App\Models\PlaylistItem;
use App\Models\Screen;
use App\Services\ScreenDataGenerator;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateScreenPlaylistEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly Screen $screen)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('Screen.' . $this->screen->id),
        ];
    }

    public function broadcastAs()
    {
        return 'playlist.switch';
    }

    public function broadcastWith()
    {
        return [
            'id' => $this->screen->playlist->id,
        ];
    }
}
