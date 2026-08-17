<?php

use App\Http\Controllers\API\AnnouncementController;
use App\Http\Controllers\API\PlaylistController;
use App\Http\Controllers\API\ScreenController;
use App\Http\Controllers\API\WeatherController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::name('api.')->group(function () {
    Route::get('/announcement', [AnnouncementController::class, 'list'])->name('announcement.list');
    Route::get('/screen/{screenId}', [ScreenController::class, 'get'])->name('screen.get');
    Route::get('/playlist/{playlistId}', [PlaylistController::class, 'get'])->name('playlist.get');
    Route::get('/weather', [WeatherController::class, 'get'])->name('weather.get');
});
