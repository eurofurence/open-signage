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

    private const FAILED_KEY = 'weather.summary.failed';

    private const LOCK_KEY = 'weather.summary.lock';

    private const FAILURE_BACKOFF_SECONDS = 30;

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

        if (Cache::get(self::FAILED_KEY)) {
            return $this->respondLastGoodOrUnavailable();
        }

        $lock = Cache::lock(self::LOCK_KEY, (int) config('services.weather.timeout') + 5);

        if (! $lock->get()) {
            $fresh = Cache::get(self::FRESH_KEY);

            if (filled($fresh)) {
                return $this->respond($fresh, false);
            }

            return $this->respondLastGoodOrUnavailable();
        }

        try {
            $summary = $this->fetch($url);

            if (blank($summary)) {
                Cache::put(self::FAILED_KEY, true, self::FAILURE_BACKOFF_SECONDS);

                return $this->respondLastGoodOrUnavailable();
            }

            $entry = [
                'fetched_at' => now()->toIso8601String(),
                'summary' => $summary,
            ];

            Cache::put(self::FRESH_KEY, $entry, (int) config('services.weather.ttl'));
            Cache::put(self::LAST_GOOD_KEY, $entry, now()->addDay());

            return $this->respond($entry, false);
        } finally {
            $lock->release();
        }
    }

    private function respondLastGoodOrUnavailable(): JsonResponse
    {
        $lastGood = Cache::get(self::LAST_GOOD_KEY);

        if (blank($lastGood)) {
            return response()->json(['message' => 'Weather data is unavailable.'], 503);
        }

        return $this->respond($lastGood, true);
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

        if (! is_array($summary) || ! is_array($summary['fsi'] ?? null)) {
            Log::warning('Weather api returned an unexpected payload shape.');

            return null;
        }

        return $summary;
    }

    private function respond(array $entry, bool $stale): JsonResponse
    {
        return response()->json(array_replace($entry, ['stale' => $stale]));
    }
}
