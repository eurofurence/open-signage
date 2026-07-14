<?php

namespace App\Providers;

use App\Channels\AdminChannel;
use App\Models\Announcement;
use App\Models\Artwork;
use App\Models\PlaylistItem;
use App\Models\RoomScreen;
use App\Models\ScheduleEntry;
use App\Models\Screen;
use App\Observers\AnnouncementObserver;
use App\Observers\ArtworkObserver;
use App\Observers\PlaylistItemObserver;
use App\Observers\RoomObserver;
use App\Observers\RoomScreenObserver;
use App\Observers\ScheduleObserver;
use App\Observers\ScreenObserver;
use App\Providers\Socialite\SocialiteIdentityProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Contracts\Factory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Screen::observe(ScreenObserver::class);
        PlaylistItem::observe(PlaylistItemObserver::class);
        Announcement::observe(AnnouncementObserver::class);
        Artwork::observe(ArtworkObserver::class);
        ScheduleEntry::observe(ScheduleObserver::class);
        RoomScreen::observe(RoomScreenObserver::class);

        Notification::extend('admin', function ($app) {
            return new AdminChannel();
        });

        $socialite = $this->app->make(Factory::class);

        $socialite->extend('identity', function () use ($socialite) {
            return $socialite->buildProvider(SocialiteIdentityProvider::class, config('services.identity'));
        });
    }
}
