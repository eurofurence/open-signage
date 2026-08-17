<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Artwork extends Model
{
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'managed' => 'boolean',
    ];

    public function scopeManaged(Builder $query): Builder
    {
        return $query->where('managed', true);
    }

    public function files(): array
    {
        return array_values(array_filter([
            $this->file_horizontal,
            $this->file_vertical,
            $this->file_banner,
        ]));
    }
}
