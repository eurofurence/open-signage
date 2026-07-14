<?php

namespace App\Events;

use App\Models\PlaylistItem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class UpdatePlaylistItemEvent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(public PlaylistItem $playlistItem, public readonly string $action)
    {
    }

    public function broadcastOn(): array
    {
        return $this->playlistItem->playlist->screens->map(function ($screen) {
            return new Channel('Screen.' . $screen->id);
        })->toArray();
    }

    public function broadcastAs(): string
    {
        return 'playlistItem.' . $this->action;
    }

    public function broadcastWith(): array
    {
        $playlistItem = $this->playlistItem;
        $playlistItem->loadMissing(['page', 'layout']);
        $playlistItem->makeHidden(['playlist']);
        $playlistItem->page->makeHidden(['schema']);
        return $playlistItem->toArray();
    }
}
