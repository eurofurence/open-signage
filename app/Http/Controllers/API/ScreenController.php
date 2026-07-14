<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Screen;

class ScreenController extends Controller
{
    public function get(string $screenId)
    {
        return Screen::query()
            ->where('id', $screenId)
            ->setEagerLoads([])
            ->with('rooms')
            ->first()
            ->toArray();
    }
}
