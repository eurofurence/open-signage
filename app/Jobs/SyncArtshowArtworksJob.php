<?php

namespace App\Jobs;

use App\Models\Artwork;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Throwable;

class SyncArtshowArtworksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const DIRECTORY = 'artworks';

    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function __construct() {}

    public function handle(): void
    {
        $lock = Cache::lock('sync-artshow-artworks', 600);

        if (! $lock->get()) {
            Log::info('Art show sync skipped, another sync is still running.');

            return;
        }

        try {
            $images = $this->fetchImageList();

            if (is_null($images)) {
                return;
            }

            $images->each(fn (array $image) => $this->syncImage($image['url'], $image['checksum']));

            $this->removeVanishedArtworks($images->keys()->all());
        } finally {
            $lock->release();
        }
    }

    private function fetchImageList(): ?Collection
    {
        $imagesUrl = config('services.artshow.images');

        if (blank($imagesUrl)) {
            Log::warning('Art show sync skipped, no image list url configured.');

            return null;
        }

        try {
            $response = Http::timeout((int) config('services.artshow.timeout'))->get($imagesUrl);
        } catch (ConnectionException $exception) {
            Log::warning("Art show sync failed, {$imagesUrl} was unreachable: {$exception->getMessage()}");

            return null;
        }

        if ($response->failed()) {
            Log::warning("Art show sync failed, {$imagesUrl} responded with {$response->status()}.");

            return null;
        }

        $images = $response->json('images');

        if (! is_array($images)) {
            Log::warning("Art show sync failed, {$imagesUrl} did not return an image list.");

            return null;
        }

        return collect($images)
            ->filter(fn ($image) => is_array($image) && filled($image['url'] ?? null) && filled($image['checksum'] ?? null))
            ->keyBy('url');
    }

    private function syncImage(string $url, string $checksum): void
    {
        $artwork = Artwork::managed()->where('external_url', $url)->first();

        if ($artwork && $artwork->external_checksum === $checksum && $this->isStillStored($artwork)) {
            return;
        }

        try {
            $response = Http::timeout((int) config('services.artshow.timeout'))->get($url);
        } catch (ConnectionException $exception) {
            Log::warning("Art show sync could not download {$url}: {$exception->getMessage()}");

            return;
        }

        if ($response->failed()) {
            Log::warning("Art show sync could not download {$url}, responded with {$response->status()}.");

            return;
        }

        $contents = $response->body();

        if ($this->isSha256($checksum) && hash('sha256', $contents) !== $checksum) {
            Log::warning("Art show sync discarded {$url}, checksum did not match the downloaded file.");

            return;
        }

        try {
            $image = Image::read($contents);
        } catch (Throwable $exception) {
            Log::warning("Art show sync discarded {$url}, it could not be read as an image.", ['exception' => $exception]);

            return;
        }

        $isVertical = $image->height() > $image->width();
        $path = self::DIRECTORY . '/' . Str::random(40) . '.' . $this->extensionFor($url, $image->origin()->fileExtension());

        Storage::put($path, $contents);

        $replacedFiles = $artwork ? $artwork->files() : [];

        $attributes = [
            'managed' => true,
            'external_url' => $url,
            'external_checksum' => $checksum,
            'file_horizontal' => $isVertical ? null : $path,
            'file_vertical' => $isVertical ? $path : null,
        ];

        if ($artwork) {
            $artwork->update($attributes);
        } else {
            Artwork::create(array_merge($attributes, ['name' => $this->nameFor($url)]));
        }

        collect($replacedFiles)
            ->reject(fn (string $file) => $file === $path)
            ->each(fn (string $file) => $this->deleteFile($file));
    }

    private function removeVanishedArtworks(array $knownUrls): void
    {
        Artwork::managed()
            ->whereNotIn('external_url', $knownUrls)
            ->get()
            ->each(function (Artwork $artwork) {
                $files = $artwork->files();
                $artwork->delete();
                collect($files)->each(fn (string $file) => $this->deleteFile($file));
            });
    }

    private function isStillStored(Artwork $artwork): bool
    {
        $files = $artwork->files();

        if (empty($files) || collect($files)->contains(fn (string $file) => ! Storage::exists($file))) {
            return false;
        }

        if (collect($files)->contains(fn (string $file) => ! Storage::exists($file . '.webp'))) {
            Artisan::call('images:convert', ['artworkId' => $artwork->id]);
        }

        return true;
    }

    private function deleteFile(string $file): void
    {
        Storage::delete([$file, $file . '.webp']);
    }

    private function isSha256(string $checksum): bool
    {
        return (bool) preg_match('/^[a-f0-9]{64}$/i', $checksum);
    }

    private function extensionFor(string $url, ?string $originExtension): string
    {
        $extension = Str::lower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));

        if (in_array($extension, self::EXTENSIONS, true)) {
            return $extension;
        }

        return blank($originExtension) ? 'jpg' : Str::lower($originExtension);
    }

    private function nameFor(string $url): string
    {
        $name = pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_FILENAME);

        return blank($name) ? $url : $name;
    }
}
