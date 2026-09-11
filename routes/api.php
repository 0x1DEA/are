<?php

use App\Jobs\DownloadListingMedia;
use App\Jobs\GeocodeListings;
use App\Models\Listing;
use App\Models\ListingMedia;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Uri;
use MatanYadaev\EloquentSpatial\Enums\Srid;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;

Route::get('/mlsgrid:test', function (Request $request) {
    $api = 'https://api-demo.mlsgrid.com/v2/';

    $odata = 'OriginatingSystemName eq \'mred\' and MlgCanView eq true and StandardStatus eq \'Active\'';

    $query = Uri::of($api.'Property')->withQuery([
        '$filter' => $odata,
        '$expand' => 'Media,Rooms,UnitTypes',
        //        '$skip' => 5000,
        '$top' => 50,
    ]);

    $res = Http::withHeaders([
        'Accept-Encoding' => 'gzip',
        'Authorization' => 'Bearer '.config('services.mlsgrid.key'),
    ])->get($query->toString())->json('value');

    $listings = [];
    $listing_ids = [];
    $media = [];

    for ($i = 0; $i < count($res); $i++) {
        $listing_ids[] = $res[$i]['ListingId'];
        $listings[] = [
            'listing_agent_mls_id' => $res[$i]['ListAgentMlsId'],
            'mls_id' => $res[$i]['ListingId'],

            'status' => $res[$i]['StandardStatus'],
            'neighborhood' => $res[$i]['MLSAreaMajor'] ?? null,
            'year_built' => $res[$i]['YearBuilt'] ?? null,
            'public_remarks' => $res[$i]['PublicRemarks'] ?? null,
            'private_remarks' => $res[$i]['PrivateRemarks'] ?? null,

            'living_area_sq_ft' => $res[$i]['LivingArea'] ?? null,

            'address_number' => $res[$i]['StreetNumber'],
            'address_direction' => $res[$i]['StreetDirPrefix'] ?? null,
            'address_street' => $res[$i]['StreetName'] ?? '',
            'address_street_suffix' => $res[$i]['StreetSuffix'] ?? null,
            'address_unit' => $res[$i]['UnitNumber'] ?? null,

            'address_city' => $res[$i]['City'],
            'address_state' => $res[$i]['StateOrProvince'],
            'address_postal' => $res[$i]['PostalCode'] ?? null,

            'bedrooms' => $res[$i]['BedroomsTotal'] ?? 0,
            'total_bathrooms' => $res[$i]['BathroomsTotalInteger'] ?? 0,
            'full_bathrooms' => $res[$i]['BathroomsFull'] ?? 0,
            'half_bathrooms' => $res[$i]['BathroomsHalf'] ?? 0,

            'feed_idx' => in_array('IDX', $res[$i]['MlgCanUse']),
            'feed_vow' => in_array('VOW', $res[$i]['MlgCanUse']),
            'feed_bo' => in_array('BO', $res[$i]['MlgCanUse']),
            'feed_pt' => in_array('PT', $res[$i]['MlgCanUse']),

            'sale_price_original' => $res[$i]['OriginalListPrice'] ?? null,
            'sale_price' => $res[$i]['ListPrice'] ?? null,
            'rent_price_original' => $res[$i]['MRD_ORP'] ?? null,
            'rent_price' => $res[$i]['MRD_RP'] ?? null,

            'mls_data' => json_encode($res[$i]),
            'mls_fetched_at' => now(),

            'listed_at' => Carbon::make($res[$i]['OriginalEntryTimestamp']),
        ];

        if (array_key_exists('Media', $res[$i])) {
            for ($j = 0; $j < count($res[$i]['Media']); $j++) {
                $m = $res[$i]['Media'][$j];

                $media[] = [
                    'key' => $m['MediaKey'],
                    'mls_listing_id' => $m['ResourceRecordID'],
                    'source_url' => $m['MediaURL'],

                    'type' => $m['MediaObjectID'] ?? null,
                    'order' => $m['Order'] ?? null,

                    'width' => $m['ImageWidth'],
                    'height' => $m['ImageHeight'],

                    'mls_modified_at' => $m['MediaModificationTimestamp'] ? Carbon::make($m['MediaModificationTimestamp']) : null,
                ];
            }
        }
    }

    Listing::upsert($listings, 'mls_id');
    ListingMedia::upsert($media, 'key');

    GeocodeListings::dispatch();
    DownloadListingMedia::dispatch($listing_ids);

    return 1;
});

Route::get('search/{coordinates}', function (Request $request, $coordinates) {
    $coords = array_map(function ($item) {
        $ll = explode(',', $item);

        $lat = clamp(floatval($ll[0]), -90, 90);
        $lng = fmod(floatval($ll[1]) + 180, 360) - 180;

        return [$lat, $lng];
    }, explode(';', $coordinates));

    $filter = $request->collect('filter');
    $sort = $request->collect('sort');

    $q = Listing::query()->whereNotNull('coordinates');

    $q->whereWithin('coordinates', Polygon::fromArray([
        'type' => 'Polygon',
        'coordinates' => [$coords],
    ], Srid::WGS84));

    if ($sort->get('field') === 'listed_at') {
        if ($sort->get('dir') === 'asc') {
            $q->orderBy('listed_at');
        }
        if ($sort->get('dir') === 'desc') {
            $q->orderByDesc('listed_at');
        }
    }

    $price_column = null;

    if ($filter->get('type') === 'rent') {
        $price_column = 'rent_price';
        $q->whereNull($price_column);
    } elseif ($filter->get('type') === 'sale') {
        $price_column = 'sale_price';
        $q->whereNull($price_column);
    }

    if ($filter->has('price_min')) {
        if ($price_column === null) {
            $q->where(function (Builder $query) use ($filter) {
                $query->where('rent_price', '>=', $filter->get('price_min'))
                    ->orWhere('sale_price', '>=', $filter->get('price_min'));
            });
        } else {
            $q->where($price_column, '>=', $filter->get('price_min'));
        }
    }

    if ($filter->has('price_max')) {
        if ($price_column === null) {
            $q->where(function (Builder $query) use ($filter) {
                $query->where('rent_price', '<=', $filter->get('price_max'))
                    ->orWhere('sale_price', '<=', $filter->get('price_max'));
            });
        } else {
            $q->where($price_column, '<=', $filter->get('price_max'));
        }
    }

    if ($sort->get('field') === 'price') {
        if ($price_column === null) {
            $q->orderBy('sale_price', $sort->get('dir'));
            $q->orderBy('rent_price', $sort->get('dir'));
        } else {
            $q->orderBy($price_column, $sort->get('dir'));
        }
    }

    if ($filter->has('beds_min')) {
        $q->where('bedrooms', '>=', $filter->get('beds_min'));
    }

    if ($filter->has('beds_max')) {
        $q->where('bedrooms', '<=', $filter->get('beds_max'));
    }

    if ($filter->has('baths_min')) {
        $q->where('total_bathrooms', '>=', $filter->get('baths_min'));
    }

    if ($filter->has('baths_max')) {
        $q->where('total_bathrooms', '<=', $filter->get('baths_max'));
    }

    // default: order by distance to downtown Chicago (roughly proportional to listing density)
    [$lat, $lng] = explode(',', $request->string('center', '41.90,-87.62'));
    // Whoever is responsible for this should get explosive diarrhea
    $q->orderByDistanceSphere(DB::raw('POINT(ST_Y(coordinates), ST_X(coordinates))'), new Point($lat, $lng, Srid::WGS84));

    return $q->limit(1000)
        ->where('feed_idx', true)
        ->where('status', 'Active')
        ->with(['thumbnail'])
        ->get();
});

Route::get('listing/{listing:mls_id}', function (Listing $listing) {
    return $listing->load(['thumbnail']);
});
