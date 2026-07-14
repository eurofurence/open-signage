<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Support\Facades\DB;

class AnnouncementController extends Controller
{
    public function list()
    {
        return DB::transaction(function () {
            $now = now();

            $lastUpdated = Announcement::all()
                ->max('updated_at');

            return [
                'announcements' => Announcement::all()
                    ->where('starts_at', '<', $now)
                    ->where('ends_at', '>', $now)
                    ->sortBy('starts_at')
                    ->toArray(),
                'last_update' => $lastUpdated,
            ];
        });
    }
}
