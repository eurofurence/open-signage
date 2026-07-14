<?php

namespace App\Observers;

use App\Events\UpdateAnnouncementEvent;
use App\Models\Announcement;

class AnnouncementObserver
{
    public function created(Announcement $announcement): void
    {
        $now = now();
        $later = $now->copy()->addMinutes(10);

        if ($announcement->starts_at > $later || $announcement->ends_at < $now) {
            return;
        }

        broadcast(new UpdateAnnouncementEvent($announcement, 'create'));
    }

    public function updated(Announcement $announcement): void
    {
        $now = now();
        $later = $now->copy()->addMinutes(10);

        if ($announcement->starts_at > $later || $announcement->ends_at < $now) {
            return;
        }

        broadcast(new UpdateAnnouncementEvent($announcement, 'update'));
    }

    public function deleted(Announcement $announcement): void
    {
        broadcast(new UpdateAnnouncementEvent($announcement, 'delete'));
    }

    public function restored(Announcement $announcement): void
    {
    }

    public function forceDeleted(Announcement $announcement): void
    {
    }
}
