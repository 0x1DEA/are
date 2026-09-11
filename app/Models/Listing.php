<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'mls_fetched_at' => 'datetime',
        'listed_at' => 'datetime',
        'mls_data' => 'array',
    ];

    protected $guarded = [];

    public function thumbnail(): Listing|HasOne
    {
        return $this->hasOne(ListingMedia::class, 'mls_listing_id', 'mls_id')->whereNotNull('url');
    }
}
