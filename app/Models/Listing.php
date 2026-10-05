<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
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
        'modified_at' => 'datetime',
        'mls_data' => 'array',
    ];

    protected $guarded = [];

    public static function arrayFromAPI(array $item): array
    {
        unset($item['Media']);

        return [
            'listing_agent_mls_id' => $item['ListAgentMlsId'],
            'mls_id' => $item['ListingId'],

            'status' => $item['StandardStatus'],
            'neighborhood' => $item['MLSAreaMajor'] ?? null,
            'year_built' => $item['YearBuilt'] ?? null,
            'public_remarks' => $item['PublicRemarks'] ?? null,
            'private_remarks' => $item['PrivateRemarks'] ?? null,

            'living_area_sq_ft' => $item['LivingArea'] ?? null,

            'address_number' => $item['StreetNumber'] ?? null,
            'address_direction' => $item['StreetDirPrefix'] ?? null,
            'address_street' => $item['StreetName'] ?? null,
            'address_street_suffix' => $item['StreetSuffix'] ?? null,
            'address_unit' => $item['UnitNumber'] ?? null,

            'address_city' => $item['City'] ?? null,
            'address_state' => $item['StateOrProvince'] ?? null,
            'address_postal' => $item['PostalCode'] ?? null,

            'bedrooms' => $item['BedroomsTotal'] ?? 0,
            'total_bathrooms' => $item['BathroomsTotalInteger'] ?? 0,
            'full_bathrooms' => $item['BathroomsFull'] ?? 0,
            'half_bathrooms' => $item['BathroomsHalf'] ?? 0,

            'feed_idx' => in_array('IDX', $item['MlgCanUse']),
            'feed_vow' => in_array('VOW', $item['MlgCanUse']),
            'feed_bo' => in_array('BO', $item['MlgCanUse']),
            'feed_pt' => in_array('PT', $item['MlgCanUse']),

            'sale_price_original' => $item['OriginalListPrice'] ?? null,
            'sale_price' => $item['ListPrice'] ?? null,
            'rent_price_original' => $item['MRD_ORP'] ?? null,
            'rent_price' => $item['MRD_RP'] ?? null,

            'mls_data' => json_encode($item),
            'mls_fetched_at' => now(),

            // created/updated but from the MLS
            'listed_at' => Carbon::make($item['OriginalEntryTimestamp']),
            'modified_at' => Carbon::make($item['ModificationTimestamp']),
        ];
    }

    public function thumbnail(): Listing|HasOne
    {
        return $this->hasOne(ListingMedia::class, 'mls_listing_id', 'mls_id')->whereNotNull('url');
    }
}
