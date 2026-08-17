<?php

namespace Tests\Feature;

use App\Events\UpdateArtworkEvent;
use App\Jobs\SyncArtshowArtworksJob;
use App\Models\Artwork;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\Laravel\Facades\Image;
use Tests\TestCase;

class ArtshowArtworkSyncTest extends TestCase
{
    use RefreshDatabase;

    private const LIST_URL = 'https://artshow.test/signage/images.json';

    private const LANDSCAPE_URL = 'https://artshow.test/signage/landscape.jpg';

    private const PORTRAIT_URL = 'https://artshow.test/signage/portrait.jpg';

    private array $images = [];

    private int $listStatus = 200;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'filesystems.default' => 'public',
            'services.artshow.images' => self::LIST_URL,
        ]);

        Storage::fake('public');
        Event::fake([UpdateArtworkEvent::class]);
        Http::fake(fn(Request $request) => $this->respond($request));
    }

    public function test_it_imports_artworks_and_detects_their_orientation()
    {
        $landscape = $this->jpeg(1920, 1080);
        $portrait = $this->jpeg(1080, 1920);

        $this->fakeArtshow([
            self::LANDSCAPE_URL => $landscape,
            self::PORTRAIT_URL => $portrait,
        ]);

        SyncArtshowArtworksJob::dispatchSync();

        $this->assertSame(2, Artwork::count());

        $horizontal = Artwork::where('external_url', self::LANDSCAPE_URL)->first();
        $this->assertTrue($horizontal->managed);
        $this->assertSame('landscape', $horizontal->name);
        $this->assertSame(hash('sha256', $landscape), $horizontal->external_checksum);
        $this->assertNotNull($horizontal->file_horizontal);
        $this->assertNull($horizontal->file_vertical);
        Storage::assertExists($horizontal->file_horizontal);
        Storage::assertExists($horizontal->file_horizontal . '.webp');

        $vertical = Artwork::where('external_url', self::PORTRAIT_URL)->first();
        $this->assertNull($vertical->file_horizontal);
        $this->assertNotNull($vertical->file_vertical);
        Storage::assertExists($vertical->file_vertical);
    }

    public function test_it_does_not_download_images_again_when_the_checksum_is_unchanged()
    {
        $this->fakeArtshow([self::LANDSCAPE_URL => $this->jpeg(1920, 1080)]);

        SyncArtshowArtworksJob::dispatchSync();

        $file = Artwork::first()->file_horizontal;

        SyncArtshowArtworksJob::dispatchSync();

        Http::assertSentCount(3);
        $this->assertSame($file, Artwork::first()->file_horizontal);
    }

    public function test_it_replaces_the_image_when_the_checksum_changed()
    {
        $this->fakeArtshow([self::LANDSCAPE_URL => $this->jpeg(1920, 1080)]);

        SyncArtshowArtworksJob::dispatchSync();

        $oldFile = Artwork::first()->file_horizontal;

        $this->fakeArtshow([self::LANDSCAPE_URL => $this->jpeg(1080, 1920)]);

        SyncArtshowArtworksJob::dispatchSync();

        $artwork = Artwork::first();

        $this->assertSame(1, Artwork::count());
        $this->assertNull($artwork->file_horizontal);
        $this->assertNotNull($artwork->file_vertical);
        Storage::assertMissing($oldFile);
        Storage::assertMissing($oldFile . '.webp');
        Storage::assertExists($artwork->file_vertical);
    }

    public function test_it_removes_managed_artworks_that_vanished_from_the_api_and_keeps_the_others()
    {
        Storage::put('manual.jpg', $this->jpeg(1920, 1080));
        $manual = Artwork::create([
            'name' => 'Manually uploaded',
            'file_horizontal' => 'manual.jpg',
        ]);

        $this->fakeArtshow([
            self::LANDSCAPE_URL => $this->jpeg(1920, 1080),
            self::PORTRAIT_URL => $this->jpeg(1080, 1920),
        ]);

        SyncArtshowArtworksJob::dispatchSync();

        $vanishing = Artwork::where('external_url', self::PORTRAIT_URL)->first();
        $surviving = Artwork::where('external_url', self::LANDSCAPE_URL)->first();

        $this->fakeArtshow([self::LANDSCAPE_URL => $this->jpeg(1920, 1080)]);

        SyncArtshowArtworksJob::dispatchSync();

        $this->assertModelMissing($vanishing);
        Storage::assertMissing($vanishing->file_vertical);
        $this->assertModelExists($surviving);
        $this->assertModelExists($manual);
        Storage::assertExists('manual.jpg');
    }

    public function test_it_keeps_managed_artworks_when_the_api_is_unreachable()
    {
        $this->fakeArtshow([self::LANDSCAPE_URL => $this->jpeg(1920, 1080)]);

        SyncArtshowArtworksJob::dispatchSync();

        $this->listStatus = 500;

        SyncArtshowArtworksJob::dispatchSync();

        $this->assertSame(1, Artwork::count());
    }

    private function fakeArtshow(array $images): void
    {
        $this->images = $images;
    }

    private function respond(Request $request)
    {
        if ($request->url() === self::LIST_URL) {
            if ($this->listStatus !== 200) {
                return Http::response('', $this->listStatus);
            }

            return Http::response([
                'images' => collect($this->images)
                    ->map(fn(string $contents, string $url) => ['url' => $url, 'checksum' => hash('sha256', $contents)])
                    ->values()
                    ->all(),
            ]);
        }

        if (!array_key_exists($request->url(), $this->images)) {
            return Http::response('', 404);
        }

        return Http::response($this->images[$request->url()], 200, ['Content-Type' => 'image/jpeg']);
    }

    private function jpeg(int $width, int $height): string
    {
        return (string) Image::create($width, $height)
            ->fill($width > $height ? '#ff0000' : '#0000ff')
            ->encode(new JpegEncoder());
    }
}
