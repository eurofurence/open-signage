<?php

namespace App\Observers;

use App\Events\UpdateScheduleEvent;
use App\Models\ScheduleEntry;

class ScheduleObserver
{
    public function created(ScheduleEntry $scheduleEntry): void
    {
        broadcast(new UpdateScheduleEvent($scheduleEntry, 'create'));
    }

    public function updated(ScheduleEntry $scheduleEntry): void
    {
        broadcast(new UpdateScheduleEvent($scheduleEntry, 'update'));
    }

    public function deleted(ScheduleEntry $scheduleEntry): void
    {
        broadcast(new UpdateScheduleEvent($scheduleEntry, 'delete'));
    }

    public function restored(ScheduleEntry $scheduleEntry): void
    {
    }

    public function forceDeleted(ScheduleEntry $scheduleEntry): void
    {
    }
}
