<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Storage;

class PlaylistItem extends Model
{
    use HasFactory;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'playlist_id' => 'integer',
        'page_id' => 'integer',
        'layout_id' => 'integer',
        'content' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function playlist(): BelongsTo
    {
        return $this->belongsTo(Playlist::class);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function layout(): BelongsTo
    {
        return $this->belongsTo(Layout::class);
    }

    protected static function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function toScreenArray(): array
    {
        $this->loadMissing(['page', 'layout']);
        $this->page?->makeHidden(['schema']);

        return array_replace($this->toArray(), ['content' => $this->parsedContent()]);
    }

    public function parsedContent(): array
    {
        $schema = collect($this->page->schema);
        $data = collect($this->content)->map(function ($value, $key) use (&$schema) {
            $property = $schema->where('property', $key)->first();
            if (is_null($property)) {
                return $value;
            }
            if ($property['type'] === 'ImageInput' || $property['type'] === 'FileInput') {
                if (blank($value)) {
                    return $value;
                }
                if (Str::endsWith($value, ['jpg', 'jpeg', 'png'])) {
                    return Storage::url($value . '.webp');
                }

                return Storage::url($value);
            }

            return $value;
        });

        return $data->toArray();
    }
}
