<?php

use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'agents' => \App\Models\Agent::query()
            ->where('is_active', true)
            ->get(),
    ]);
})->name('home');

Route::get('/listing/{listing:mls_id}', function (Listing $listing) {
    return Inertia::render('Listings/Show', [
        'listing' => $listing,
    ]);
})->name('listing.show');


Route::get('/search', function (Request $request) {
    return Inertia::render('Search', [
        'lat' => $request->float('lat', 41.85),
        'lng' => $request->float('lng', -87.99),
        'zoom' => $request->float('zoom', 11),
    ]);
})->name('search');
