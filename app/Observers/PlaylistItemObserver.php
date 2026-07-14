<?php

namespace App\Observers;

use App\Events\UpdatePlaylistItemEvent;
use App\Jobs\ConvertAnyFileJob;
use App\Models\PlaylistItem;
use Illuminate\Support\Facades\Bus;

class PlaylistItemObserver
{
    public function created(PlaylistItem $playlistItem): void
    {
        Bus::chain([
            fn () => ConvertAnyFileJob::dispatch(),
            fn () => broadcast(new UpdatePlaylistItemEvent($playlistItem, 'create')),
        ])->dispatch();
    }

    public function updated(PlaylistItem $playlistItem): void
    {
        // Get Playlist from PlaylistItem and then Screen from Playlist and run broadcast on each
        Bus::chain([
            fn () => ConvertAnyFileJob::dispatch(),
            fn () => broadcast(new UpdatePlaylistItemEvent($playlistItem, 'update')),
        ])->dispatch();
    }

    public function deleted(PlaylistItem $playlistItem): void
    {
        broadcast(new UpdatePlaylistItemEvent($playlistItem, 'delete'));
    }

    public function restored(PlaylistItem $playlistItem): void
    {
    }

    public function forceDeleted(PlaylistItem $playlistItem): void
    {
    }
}
