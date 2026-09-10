<?php

use App\Models\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Uri;
use MatanYadaev\EloquentSpatial\Enums\Srid;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/mlsgrid:test', function (Request $request) {
    $api = 'https://api-demo.mlsgrid.com/v2/';

    $odata = 'OriginatingSystemName eq \'mred\' and MlgCanView eq true and StandardStatus eq \'Active\'';

    $query = Uri::of($api.'Property')->withQuery([
        '$filter' => $odata,
        '$expand' => 'Media,Rooms,UnitTypes',
        //        '$skip' => 5000,
        //        '$top' => 100,
    ]);

    $res = Http::withHeaders([
        'Accept-Encoding' => 'gzip',
        'Authorization' => 'Bearer '.'568376abdeb35cc5754977885074d3cea4ebe60d',
    ])->get($query->toString())->json('value');

    $listings = [];

    for ($i = 0; $i < count($res); $i++) {
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

            'listed_at' => \Carbon\Carbon::make($res[$i]['OriginalEntryTimestamp']),
        ];
    }

    Listing::upsert($listings, 'mls_id');

    return 1;
});

Route::get('/geocodio:test', function (Request $request) {
    $geocoder = new Geocodio\Geocodio;
    $geocoder->setApiKey('8c6c08586f26baa5822f6cc8cc0a862252fc6a0');

    $listings = Listing::query()
        ->limit(500)
        ->whereNull('geo_fetched_at')
        ->select([
            'mls_id',
            'address_number',
            'address_direction',
            'address_street',
            'address_street_suffix',
            'address_unit',
            'address_city',
            'address_state',
            'address_postal',
        ])
        ->get()
        ->mapWithKeys(function (Listing $l) {
            $street = implode(' ', array_filter([
                $l->address_number,
                $l->address_direction,
                $l->address_street,
                $l->address_street_suffix,
            ], fn ($value) => $value !== null));

            return [
                $l->mls_id => [
                    'street' => $street,
                    'city' => $l->address_city,
                    'state_province' => $l->address_state,
                    'postal_code' => $l->address_postal,
                    'country' => 'USA',
                ],
            ];
        });

    if ($listings->isEmpty()) {
        return 0;
    }

    $updates = [];

    $results = $geocoder->geocode($listings->toArray())['results'];

    foreach ($results as $mls_id => $result) {
        $coordinates = null;

        foreach ($result['response']['results'] as $address) {
            if ($address['accuracy'] === 1) {
                //                $coordinates = new Point($address['location']['lat'], $address['location']['lng'], Srid::WGS84->value);
                $coordinates = DB::raw("ST_GeomFromText('POINT({$address['location']['lat']} {$address['location']['lng']})', 4326)");
            }
        }

        $updates[] = [
            'mls_id' => $mls_id,
            'coordinates' => $coordinates,
            'geo_data' => json_encode($result),
            'geo_fetched_at' => now(),
        ];
    }

    Listing::upsert($updates, 'mls_id', ['coordinates', 'geo_data', 'geo_fetched_at']);

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
        ->get();
});

Route::get('proxy/{url}', function ($url) {
    if (! $url || ! filter_var($url, FILTER_VALIDATE_URL)) {
        return response()->json(['error' => 'Invalid URL'], 400);
    }

    $response = Http::withHeaders([
        'Host' => 's3.amazonaws.com',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'Accept-Encoding' => 'gzip, deflate, br, zstd',
        'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',
    ])->get($url);

    if ($response->failed()) {
        return response()->json(['error' => 'Unable to fetch image'], 404);
    }

    $contentType = $response->header('Content-Type') ?? 'image/jpeg';

    return response($response->body(), 200)->header('Content-Type', $contentType);
})->where('url', '.*');

Route::get('listing/{listing:mls_id}', function (Listing $listing) {
    return $listing;
});
