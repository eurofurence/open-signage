<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\Artwork;
use App\Models\PlaylistItem;
use App\Models\Project;
use App\Models\ScheduleEntry;
use App\Models\Screen;
use Illuminate\Support\Facades\Storage;

class ScreenDataGenerator
{
    public static function announcements(): array
    {
        return Announcement::all()->toArray();
    }

    public static function pages(Screen $screen)
    {
        $screen->loadMissing(['playlist.playlistItems.layout.project', 'playlist.playlistItems.page.project']);
        return $screen->playlist->playlistItems
            ->reject(fn(PlaylistItem $playlistItem) => $playlistItem->is_active === false)
            ->sortBy('sort')
            ->map(fn(PlaylistItem $playlistItem) => [
                'layout' => [
                    'component' => $playlistItem->layout->component,
                    'path' => $playlistItem->layout->project->path,
                ],
                'path' => $playlistItem->page->project->path,
                'component' => $playlistItem->page->component,
                'props' => $playlistItem->parsedContent(),
                'duration' => $playlistItem->duration,
                'title' => $playlistItem->title ?? '',
                'starts_at' => $playlistItem->starts_at,
                'ends_at' => $playlistItem->ends_at,
            ])->values();
    }

    public static function screen(Screen $screen): array
    {
        $data = $screen
            ->loadMissing('rooms', 'room')
            ->toArray();
        $data['rooms'] = collect($data['rooms'])->sortBy(fn($room) => $room['pivot']['sort'])->values();
        return $data;
    }

    public static function playlist(Screen $screen): array
    {
        $playlist = $screen->playlist()->first()
            ->loadMissing('playlistItems', 'playlistItems.page', 'playlistItems.layout');

        return array_replace($playlist->toArray(), [
            'playlist_items' => $playlist->playlistItems
                ->map(fn(PlaylistItem $playlistItem) => $playlistItem->toScreenArray())
                ->values()
                ->toArray(),
        ]);
    }

    public static function artworks(): array
    {
        return Artwork::all()->map(fn(Artwork $artwork) => [
            'id' => $artwork->id,
            'name' => $artwork->name,
            'artist' => $artwork->artist,
            'horizontal' => (empty($artwork->file_horizontal)) ? null : Storage::url($artwork->file_horizontal),
            'vertical' => (empty($artwork->file_vertical)) ? null : Storage::url($artwork->file_vertical),
            'banner' => (empty($artwork->file_banner)) ? null : Storage::url($artwork->file_banner),
        ])->toArray();
    }

    public static function schedule(): array
    {
        return ScheduleEntry::with(['room', 'scheduleType', 'scheduleOrganizer'])
            ->where('project_id', Project::where('path', config('app.default_project'))->firstOrFail()->id)
            ->orderBy('starts_at')->get()->toArray();
    }
}
