<?php

use App\Models\Listing;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'agents' => \App\Models\Agent::all(),
    ]);
})->name('home');

Route::get('/listing/{listing:mls_id}', function (Listing $listing) {
    return Inertia::render('Listings/Show', [
        'listing' => $listing,
    ]);
})->name('listing.show');


Route::get('/search', function () {
    return Inertia::render('Search');
})->name('search');
