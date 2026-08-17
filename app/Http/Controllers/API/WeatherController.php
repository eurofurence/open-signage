<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WeatherController extends Controller
{
    private const FRESH_KEY = 'weather.summary.fresh';

    private const LAST_GOOD_KEY = 'weather.summary.last_good';

    public function get(): JsonResponse
    {
        $url = config('services.weather.url');

        if (blank($url)) {
            return response()->json(['message' => 'No weather api url configured.'], 503);
        }

        $fresh = Cache::get(self::FRESH_KEY);

        if (filled($fresh)) {
            return $this->respond($fresh, false);
        }

        $summary = $this->fetch($url);

        if (blank($summary)) {
            $lastGood = Cache::get(self::LAST_GOOD_KEY);

            if (blank($lastGood)) {
                return response()->json(['message' => 'Weather data is unavailable.'], 503);
            }

            return $this->respond($lastGood, true);
        }

        $entry = [
            'fetched_at' => now()->toIso8601String(),
            'summary' => $summary,
        ];

        Cache::put(self::FRESH_KEY, $entry, (int) config('services.weather.ttl'));
        Cache::put(self::LAST_GOOD_KEY, $entry, now()->addDay());

        return $this->respond($entry, false);
    }

    private function fetch(string $url): ?array
    {
        try {
            $summary = Http::timeout((int) config('services.weather.timeout'))
                ->acceptJson()
                ->get($url)
                ->throw()
                ->json();
        } catch (Throwable $exception) {
            Log::warning('Weather api request failed: ' . $exception->getMessage());

            return null;
        }

        return is_array($summary) ? $summary : null;
    }

    private function respond(array $entry, bool $stale): JsonResponse
    {
        return response()->json(array_replace($entry, ['stale' => $stale]));
    }
}
