<?php

use App\Console\Commands\SingleListing;
use App\Models\Agent;
use App\Models\Listing;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'agents' => Agent::query()
            ->where('is_active', true)
            ->get(),
    ]);
})->name('home');

Route::get('/agents', function () {
    return Inertia::render('Agents', [
        'agents' => Agent::query()
            ->where('is_active', true)
            ->get(),
    ]);
})->name('agents');

Route::get('/contact', function () {
    return Inertia::render('Contact');
})->name('contact');

Route::get('/listing/{mlsno}', function (string $mlsno) {
    $listing = Listing::query()
        ->with(['thumbnail', 'media'])
        ->where('mls_id', $mlsno)
        ->first();

    if (! $listing || $listing->doesntHave('thumbnail')) {
        Artisan::call(SingleListing::class, [
            'mlsno' => $mlsno,
        ]);

        if (! $listing) {
            $listing = Listing::query()
                ->with(['thumbnail', 'media'])
                ->where('mls_id', $mlsno)
                ->firstOrFail();
        }
    }

    return Inertia::render('Listings/Show', [
        'listing' => $listing,
        'agent' => Agent::query()->oldest()->first(),
    ]);
})->name('listing.show');

Route::get('/search', function (Request $request) {
    return Inertia::render('Search', [
        'lat' => $request->float('lat', 41.85),
        'lng' => $request->float('lng', -87.99),
        'zoom' => $request->float('zoom', 11),
    ]);
})->name('search');

Route::post('/contact', function (Request $request) {
    $message = new Message;
    $message->email = $request->string('email');
    $message->subject = $request->string('subject');
    $message->content = $request->string('content');
    $message->save();

    return back();
});
