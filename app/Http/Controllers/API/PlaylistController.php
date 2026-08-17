<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Playlist;
use App\Models\PlaylistItem;

class PlaylistController extends Controller
{
    public function get(string $playlistId)
    {
        $playlist = Playlist::query()
            ->where('id', $playlistId)
            ->with(['playlistItems', 'playlistItems.page', 'playlistItems.layout'])
            ->first();

        return array_replace($playlist->toArray(), [
            'playlist_items' => $playlist->playlistItems
                ->map(fn (PlaylistItem $item) => $item->toScreenArray())
                ->values()
                ->toArray(),
        ]);
    }
}
