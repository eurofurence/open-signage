<?php

namespace App\Jobs;

use App\Models\Project;
use App\Models\Room;
use App\Models\ScheduleEntry;
use Carbon\CarbonInterval;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class SyncEurofurenceScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
    }

    public function handle(): void
    {
        $pretalxDomain = config('services.pretalx.domain');
        $pretalxSchedule = config('services.pretalx.schedule');
        $scheduleJsonUrl = "https://$pretalxDomain/$pretalxSchedule/api/schedule";

        $schedule = Http::get($scheduleJsonUrl)->json();

        $project = Project::where('path', config('app.default_project'))->firstOrFail();

        $scheduleRooms = array_values(array_reduce($schedule['days'], function ($carry, $day) use ($project) {
            $scheduleRooms = array_map(function ($slot) use ($project) {
                return [
                    'project_id' => $project->id,
                    'external_id' => $slot['room']['id'],
                    'name' => $slot['room']['name'],
                ];
            }, $day['slots']);

            foreach ($scheduleRooms as $room) {
                $carry[$room['external_id']] = $room;
            }

            return $carry;
        }, []));

        Room::upsert($scheduleRooms, 'external_id', ['name']);

        $scheduleEntries = array_reduce($schedule['days'], function ($carry, $day) use ($project) {
            $scheduleEntries = array_map(function ($slot) use ($project) {
                $slot["start"] = new Carbon($slot["start"]);
                $slot["end"] = new Carbon($slot["end"]);
                $slot["delay"] = 0;

                $room = Room::where('project_id', $project->id)
                    ->where('external_id', $slot['room']['id'])
                    ->firstOrFail();

                if (config('app.enable_delay_detection')) {
                    $currentEntry = ScheduleEntry::where('project_id', $project->id)
                        ->where('external_id', $slot['id'])
                        ->first();

                    if ($currentEntry) {
                        $delay = $currentEntry->starts_at->diff($slot['start']);
                        $inLessThanFourHours = $currentEntry->starts_at->diff(Carbon::now())->compare(CarbonInterval::make("4 hours")) <= 0;
                        $lessThanFourHoursDelay = $delay->compare(CarbonInterval::make("4 hours")) <= 0;

                        if ($currentEntry->delay >= 0 || ($inLessThanFourHours && $lessThanFourHoursDelay)) {
                            $length = $slot['start']->diff($slot['end']);
                            $slot['start'] = $currentEntry->starts_at;
                            $slot['end'] = $currentEntry->starts_at->add($length);
                            $slot['delay'] = $delay->floor('minutes')->total('minutes');
                        }
                    }
                }

                return [
                    'project_id' => $project->id,
                    'external_id' => $slot['code'],
                    'room_id' => $room->id,
                    'title' => $slot['title'],
                    'description' => $slot['description'],
                    'starts_at' => $slot['start'],
                    'ends_at' => $slot['end'],
                    'automation' => '{}',
                    'flags' => '{}',
                    'delay' => $slot['delay'],
                ];
            }, $day['slots']);

            return array_merge($carry, $scheduleEntries);
        }, []);

        ScheduleEntry::upsert($scheduleEntries, 'external_id', ['room_id', 'title', 'description', 'delay', 'starts_at', 'ends_at']);
    }
}
