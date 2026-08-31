<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

class Listing extends Model
{
    /** @use HasFactory<\Database\Factories\ListingFactory> */
    use HasFactory;
    use HasSpatial;

    protected $casts = [
        'coordinates' => Point::class,
        'geo_fetched_at' => 'datetime',
        'data' => 'array',
    ];

    protected $guarded = [];
}
