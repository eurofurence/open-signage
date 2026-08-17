<?php

namespace App\Jobs;

use App\Events\UpdateScheduleEvent;
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
use RuntimeException;

class SyncEurofurenceScheduleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle(): void
    {
        $pretalxDomain = config('services.pretalx.domain');
        $pretalxSchedule = config('services.pretalx.schedule');
        $scheduleJsonUrl = "https://{$pretalxDomain}/{$pretalxSchedule}/api/schedule";

        $schedule = Http::get($scheduleJsonUrl)->json();

        $project = Project::where('path', config('app.default_project'))->firstOrFail();

        $schedule['days'] = array_map(function ($day) {
            $day['slots'] = array_values(array_filter($day['slots'], fn ($slot) => filled($slot['room']['id'] ?? null)
                && filled($slot['code'] ?? null)
                && filled($slot['start'] ?? null)
                && filled($slot['end'] ?? null)));

            return $day;
        }, $schedule['days']);

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

        $rooms = Room::where('project_id', $project->id)
            ->whereNotNull('external_id')
            ->get()
            ->keyBy('external_id');

        $currentEntries = ScheduleEntry::where('project_id', $project->id)
            ->whereNotNull('external_id')
            ->get()
            ->keyBy('external_id');

        $scheduleEntries = array_reduce($schedule['days'], function ($carry, $day) use ($project, $rooms, $currentEntries) {
            $scheduleEntries = array_map(function ($slot) use ($project, $rooms, $currentEntries) {
                $slot['start'] = new Carbon($slot['start']);
                $slot['end'] = new Carbon($slot['end']);
                $slot['delay'] = 0;

                $externalId = $slot['code'] . '-' . $slot['start']->toDateString();

                $room = $rooms->get($slot['room']['id']) ?? throw new RuntimeException("Room {$slot['room']['id']} missing after upsert");

                if (config('app.enable_delay_detection')) {
                    $currentEntry = $currentEntries->get($externalId);

                    if ($currentEntry) {
                        $delay = $currentEntry->starts_at->diff($slot['start']);
                        $inLessThanFourHours = $currentEntry->starts_at->diff(Carbon::now())->compare(CarbonInterval::make('4 hours')) <= 0;
                        $lessThanFourHoursDelay = $delay->compare(CarbonInterval::make('4 hours')) <= 0;

                        if ($currentEntry->delay > 0 || ($inLessThanFourHours && $lessThanFourHoursDelay)) {
                            $length = $slot['start']->diff($slot['end']);
                            $slot['start'] = $currentEntry->starts_at;
                            $slot['end'] = $currentEntry->starts_at->add($length);
                            $slot['delay'] = $delay->floor('minutes')->total('minutes');
                        }
                    }
                }

                return [
                    'project_id' => $project->id,
                    'external_id' => $externalId,
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

        $externalIds = array_column($scheduleEntries, 'external_id');

        $signaturesBefore = ScheduleEntry::where('project_id', $project->id)
            ->whereIn('external_id', $externalIds)
            ->get()
            ->mapWithKeys(fn (ScheduleEntry $entry) => [$entry->external_id => $this->signature($entry)]);

        ScheduleEntry::upsert($scheduleEntries, 'external_id', ['room_id', 'title', 'description', 'delay', 'starts_at', 'ends_at']);

        ScheduleEntry::with('room')
            ->where('project_id', $project->id)
            ->whereIn('external_id', $externalIds)
            ->get()
            ->each(function (ScheduleEntry $entry) use ($signaturesBefore) {
                $before = $signaturesBefore->get($entry->external_id);

                if ($before === $this->signature($entry)) {
                    return;
                }

                broadcast(new UpdateScheduleEvent($entry, is_null($before) ? 'create' : 'update'));
            });

        if ($externalIds !== []) {
            ScheduleEntry::where('project_id', $project->id)
                ->whereNotNull('external_id')
                ->whereNotIn('external_id', $externalIds)
                ->get()
                ->each
                ->delete();
        }
    }

    private function signature(ScheduleEntry $entry): string
    {
        return implode('|', [
            $entry->room_id,
            $entry->title,
            $entry->description,
            $entry->starts_at?->toIso8601String(),
            $entry->ends_at?->toIso8601String(),
            $entry->delay,
        ]);
    }
}
