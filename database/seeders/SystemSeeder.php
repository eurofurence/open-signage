<?php

namespace Database\Seeders;

use App\Enums\ResourceOwnership;
use App\Models\Page;
use App\Models\Project;
use App\Settings\GeneralSettings;
use Illuminate\Database\Seeder;

class SystemSeeder extends Seeder
{
    public function run(): void
    {
        $project = Project::updateOrCreate([
            'path' => 'system',
        ], [
            'name' => 'System',
            'path' => 'System',
            'type' => ResourceOwnership::SYSTEM,
        ]);

        $pageScreenIdent = $project->pages()->updateOrCreate([
            'component' => 'ScreenIdentification',
        ], [
            'name' => 'System - Screen Identification',
            'component' => 'ScreenIdentification',
        ]);

        $layoutScreenIdent = $project->layouts()->updateOrCreate([
            'component' => 'None',
        ], [
            'name' => 'System - None',
            'component' => 'None',
        ]);

        $playlist = $project->playlists()->firstOrCreate([
            'name' => 'Screen Identification',
        ]);
        $playlist->playlistItems()->updateOrCreate([
            'page_id' => $pageScreenIdent->id,
            'layout_id' => $layoutScreenIdent->id,
        ], [
            'duration' => 0,
        ]);

        $settings = app(GeneralSettings::class);
        if (empty($settings->playlist_id)) {
            $settings->playlist_id = $playlist->id;
            $settings->save();
        }

        /**
         * Artwork Autoplay System Page
         */
        $artworkPage = $project->pages()->updateOrCreate([
            'component' => 'ArtworkAutoplay',
        ], [
            'name' => 'System - Artwork Autoplay',
            'component' => 'ArtworkAutoplay',
            'schema' => [
                [
                    'name' => 'Show Duration (ms)',
                    'property' => 'playSpeed',
                    'type' => 'TextInput',
                ],
                [
                    'name' => 'Transition Duration (ms)',
                    'property' => 'transition',
                    'type' => 'TextInput',
                ],
            ],
        ]);

        $fullScreenFlv = $project->pages()->updateOrCreate([
            'component' => 'FullScreenFlv',
        ], [
            'name' => 'System - Full Screen Flv',
            'component' => 'FullScreenFlv',
            'schema' => [
                [
                    'name' => 'Stream URL (FLV)',
                    'property' => 'streamUrl',
                    'type' => 'TextInput',
                ],
                [
                    'name' => 'Audio Muted',
                    'property' => 'muted',
                    'type' => 'Checkbox',
                ],
            ],
        ]);

        $fullScreenImage = $project->pages()->updateOrCreate([
            'component' => 'FullScreenImage',
        ], [
            'name' => 'System - Full Screen Image',
            'component' => 'FullScreenImage',
            'schema' => [
                [
                    'name' => 'Image',
                    'property' => 'image',
                    'type' => 'ImageInput',
                ],
                [
                    'name' => 'Cover full screen (Image will be zoomed in to cover the entire screen)',
                    'property' => 'cover',
                    'type' => 'Checkbox',
                ],
            ],
        ]);

        $fullScreenVideo = $project->pages()->updateOrCreate([
            'component' => 'FullScreenVideo',
        ], [
            'name' => 'System - Full Screen Video',
            'component' => 'FullScreenVideo',
            'schema' => [
                [
                    'name' => 'Video',
                    'property' => 'video',
                    'type' => 'FileInput',
                ],
            ],
        ]);

        $weather = $project->pages()->updateOrCreate([
            'component' => 'Weather',
        ], [
            'name' => 'System - Weather',
            'component' => 'Weather',
            'schema' => [
                [
                    'name' => 'Forecast Hours (default 24)',
                    'property' => 'hoursAhead',
                    'type' => 'TextInput',
                ],
                [
                    'name' => 'Hours Of Past Context (default 4)',
                    'property' => 'pastHours',
                    'type' => 'TextInput',
                ],
                [
                    'name' => 'Refresh Interval In Seconds (default 300)',
                    'property' => 'refreshSeconds',
                    'type' => 'TextInput',
                ],
                [
                    'name' => 'Show the hourly forecast chart',
                    'property' => 'showChart',
                    'type' => 'Checkbox',
                ],
                [
                    'name' => 'Show the index breakdown next to the score',
                    'property' => 'showBreakdown',
                    'type' => 'Checkbox',
                ],
            ],
        ]);
    }
}
