<?php

use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Uri;
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
        'Authorization' => 'Bearer '.'2cb9b2a0a3f3e49cde5dc35fef9249e6c1a79178',
    ])->get($query->toString())->json('value');

    $listings = [];

    for ($i = 0; $i < count($res); $i++) {

        $listings[] = [
            'listing_agent_mls_id' => $res[$i]['ListAgentMlsId'],
            'mls_id' => $res[$i]['ListingId'],

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

            'sales_price' => $res[$i]['ListPrice'] ?? null,

            'data' => json_encode($res[$i]),
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

Route::get('search/{coordinates}/{center}', function ($coordinates, $center) {
    $coords = explode(';', $coordinates);

    $coords = array_map(function ($item) {
        $ll = explode(',', $item);

        return [floatval($ll[0]), floatval($ll[1])];
    }, $coords);

    return Listing::query()
        ->whereWithin('coordinates', Polygon::fromArray([
            'type' => 'Polygon',
            'coordinates' => [$coords],
        ]))
//        ->orderByDistanceSphere('coordinates', new Point(...explode(',', $center)))
        ->orderByDistanceSphere('coordinates', new Point(41.90, -87.62))
        ->limit(500)
        ->get();
});

Route::get('proxy/{url}', function ($url) {
    if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) return response()->json(['error' => 'Invalid URL'], 400);

    $response = Http::withHeaders([
        'Host' => 's3.amazonaws.com',
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
        'Accept-Encoding' => 'gzip, deflate, br, zstd',
        'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36',
    ])->get($url);

    if ($response->failed()) return response()->json(['error' => 'Unable to fetch image'], 404);

    $contentType = $response->header('Content-Type') ?? 'image/jpeg';

    return response($response->body(), 200)->header('Content-Type', $contentType);
})->where('url', '.*');

Route::get('listing/{listing:mls_id}', function (Listing $listing) {
    return response($listing->data)->header('Content-Type', 'application/json');
});
