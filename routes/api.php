<?php

use App\Models\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use MatanYadaev\EloquentSpatial\Enums\Srid;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;

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
        $q->whereNotNull($price_column);
    } elseif ($filter->get('type') === 'sale') {
        $price_column = 'sale_price';
        $q->whereNotNull($price_column);
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
