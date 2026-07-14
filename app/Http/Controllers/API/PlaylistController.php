<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Playlist;

class PlaylistController extends Controller
{
    public function get(string $playlistId)
    {
        $playlist = Playlist::query()
            ->where('id', $playlistId)
            ->with(['playlistItems', 'playlistItems.page', 'playlistItems.layout'])
            ->first();

        $playlist->playlistItems->each(fn($item) => $item->page->makeHidden(['schema']));

        return $playlist->toArray();
    }
}
