<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherEndpointTest extends TestCase
{
    private const SUMMARY = [
        'event' => ['name' => 'EF30'],
        'location' => ['name' => 'Hamburg', 'timezone' => 'Europe/Berlin'],
        'fsi' => ['score' => 9.9, 'label' => 'Excellent', 'color' => '#40ad3e'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config(['services.weather.url' => 'https://weather.example.test/api/summary?lang=en']);
    }

    public function test_it_proxies_the_configured_weather_api()
    {
        Http::fake(['weather.example.test/*' => Http::response(self::SUMMARY)]);

        $this->getJson(route('api.weather.get'))
            ->assertOk()
            ->assertJsonPath('stale', false)
            ->assertJsonPath('summary.fsi.score', 9.9);

        Http::assertSent(fn ($request) => $request->url() === 'https://weather.example.test/api/summary?lang=en');
    }

    public function test_it_only_calls_the_weather_api_once_per_cache_window()
    {
        Http::fake(['weather.example.test/*' => Http::response(self::SUMMARY)]);

        $this->getJson(route('api.weather.get'))->assertOk();
        $this->getJson(route('api.weather.get'))->assertOk();

        Http::assertSentCount(1);
    }

    public function test_it_keeps_serving_the_last_good_summary_when_the_weather_api_fails()
    {
        Http::fake([
            'weather.example.test/*' => Http::sequence()
                ->push(self::SUMMARY)
                ->push('nope', 500),
        ]);

        $this->getJson(route('api.weather.get'))->assertOk();

        Cache::forget('weather.summary.fresh');

        $this->getJson(route('api.weather.get'))
            ->assertOk()
            ->assertJsonPath('stale', true)
            ->assertJsonPath('summary.fsi.score', 9.9);
    }

    public function test_it_reports_unavailable_when_there_is_nothing_to_serve()
    {
        Http::fake(['weather.example.test/*' => Http::response('nope', 500)]);

        $this->getJson(route('api.weather.get'))->assertStatus(503);
    }

    public function test_it_reports_unavailable_without_a_configured_url()
    {
        config(['services.weather.url' => null]);
        Http::fake();

        $this->getJson(route('api.weather.get'))->assertStatus(503);

        Http::assertNothingSent();
    }
}
