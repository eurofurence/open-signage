<?php

namespace App\Listeners;

use App\Events\UpdateAnnouncementEvent;
use App\Events\UpdateArtworkEvent;
use App\Events\UpdatePlaylistItemEvent;
use App\Events\UpdateScheduleEvent;
use App\Events\UpdateScreenPlaylistEvent;
use Illuminate\Support\Facades\DB;

class IncreaseVersionListener
{
    public function __construct()
    {
    }

    public function handle(UpdateAnnouncementEvent|UpdateScheduleEvent|UpdateScreenPlaylistEvent|UpdatePlaylistItemEvent|UpdateArtworkEvent $event): void
    {
        // If Event is UpdateScreenPlaylistEvent increase only one screen version
        if ($event instanceof UpdateScreenPlaylistEvent) {
            $event->screen->version++;
            $event->screen->saveQuietly();
            return;
        }

        // If Event is UpdatePlaylistItemEvent increase only affected screen version
        if ($event instanceof UpdatePlaylistItemEvent) {
            $event->playlistItem->playlist->screens->each(function ($screen) {
                $screen->version++;
                $screen->saveQuietly();
            });

            return;
        }

        // Any other event will increase all screens version
        DB::table('screens')->increment('version');
    }
}
