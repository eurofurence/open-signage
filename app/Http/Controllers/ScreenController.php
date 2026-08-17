<?php

namespace App\Http\Controllers;

use App\Models\Screen;
use App\Services\ScreenDataGenerator;
use App\Settings\GeneralSettings;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ScreenController extends Controller
{
    public function __invoke(Request $request, $slug = null)
    {
        $finalSlug = $slug ?? $request->get('kiosk') ?? null;
        abort_if(is_null($finalSlug), 400, 'No slug provided');

        $screen = Screen::with('rooms')->firstOrCreate(['slug' => $finalSlug], [
            'name' => 'New Screen ' . $finalSlug,
            'slug' => $finalSlug,
            'playlist_id' => app(GeneralSettings::class)->playlist_id,
            'provisioned' => false,
        ]);

        return Inertia::render('Main', [
            'initialScreen' => ScreenDataGenerator::screen($screen),
            'initialPlaylist' => ScreenDataGenerator::playlist($screen),
            'initialArtworks' => ScreenDataGenerator::artworks(),
            'initialAnnouncements' => ScreenDataGenerator::announcements(),
            'initialSchedule' => ScreenDataGenerator::schedule(),
        ]);
    }
}
