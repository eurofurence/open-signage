<?php

namespace App\Observers;

use App\Events\UpdateArtworkEvent;
use App\Models\Artwork;
use Illuminate\Support\Facades\Artisan;

class ArtworkObserver
{
    public function created(Artwork $artwork): void
    {
        Artisan::call('images:convert',['artworkId' => $artwork->id]);
        broadcast(new UpdateArtworkEvent($artwork, 'create'));
    }

    public function updated(Artwork $artwork): void
    {
        Artisan::call('images:convert',['artworkId' => $artwork->id]);
        broadcast(new UpdateArtworkEvent($artwork, 'update'));
    }

    public function deleted(Artwork $artwork): void
    {
        broadcast(new UpdateArtworkEvent($artwork, 'delete'));
    }

    public function restored(Artwork $artwork): void
    {
    }

    public function forceDeleted(Artwork $artwork): void
    {
    }
}
