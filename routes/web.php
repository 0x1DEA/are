<?php

use App\Console\Commands\SingleListing;
use App\Http\Controllers\UserController;
use App\Mail\ListingShare;
use App\Models\Agent;
use App\Models\Listing;
use App\Models\Message;
use App\Models\Reaction;
use App\Models\Share;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile;

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

Route::get('/listing/{mlsno}', function (Request $request, string $mlsno) {
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
        'reactions' => auth()->check() ? Reaction::query()
            ->where('user_id', $request->user()->id)
            ->where('listing_id', $mlsno)
            ->first() : null,
    ]);
})->name('listing.show');

Route::get('/login', function (Request $request) {
    return Inertia::render('Login');
})->middleware(['guest'])->name('login');

Route::get('/register', function (Request $request) {
    return Inertia::render('Register');
})->middleware(['guest'])->name('register');

Route::get('/profile', function (Request $request) {
    return Inertia::render('Profile', [
        'reactions' => $request->user()->reactions()->with(['listing', 'listing.thumbnail'])->get(),
    ]);
})->middleware(['auth'])->name('profile');

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

Route::delete('/reaction/{reaction:id}', function (Reaction $reaction) {
    $reaction->delete();

    return back();
})->middleware(['auth']);

Route::post('/listing/{mlsno}/save', function (Request $request, string $mlsno) {
    $reaction = new Reaction;
    $reaction->listing_id = $mlsno;
    $reaction->user_id = $request->user()->id;
    $reaction->save();

    return back();
})->middleware(['auth']);

Route::post('/register', function (Request $request) {
    $to = $request->string('redirect')->toString();

    if (is_string($to) && str_starts_with($to, '/') && ! str_starts_with($to, '//') && ! str_contains($to, '\\')) {
        $request->session()->put('url.intended', $to);
    }

    $request->validate([
        'turnstile' => ['required', new Turnstile],
        'name' => ['required', 'string', 'max:255'],
        'email' => [
            'required',
            'string',
            'email',
            'max:255',
            Rule::unique(User::class),
        ],
        'password' => ['required', 'string', Password::default(), 'confirmed'],
    ]);

    $user = new User;
    $user->name = $request->string('name');
    $user->email = $request->string('email');
    $user->password = Hash::make($request->string('password'));
    $user->save();

    Auth::login($user);

    return redirect()->intended();
})->middleware(['guest']);

Route::post('/listing/{listing:mls_id}/share', function (Request $request, Listing $listing) {
    $data = $request->validate([
        'sender_name' => ['required', 'string', 'max:255'],
        'sender_email' => ['required', 'email'],
        'sender_phone' => ['required', 'phone:US,MX'],

        'recipient_email' => ['required', 'email'],
        'message' => ['nullable', 'string', 'max:1000'],
        'turnstile' => ['required', new Turnstile],
    ], [
        'sender_phone.phone' => 'Phone number must be a valid phone number (US and Mexico only).',
    ]);

    $share_id = Str::ulid();

    $share = new Share;
    $share->id = $share_id;
    $share->listing_id = $listing->mls_id;
    $share->from_name = $data['sender_name'];
    $share->from_email = $data['sender_email'];
    $share->from_phone = $data['sender_phone'];
    $share->to_email = $data['recipient_email'];
    $share->to_message = $data['message'];
    if (auth()->check()) {
        $share->user_id = $request->user()->id;
    }
    $share->save();

    Mail::to($request->string('recipient_email')->toString())
        ->send(new ListingShare($listing, $data, $share, $share_id));

    return back();
});

Route::get('/share/{share:id}', function (Request $request, Share $share) {
    $share->views++;
    $share->save();

    return redirect()->route('listing.show', $share->listing_id);
})->name('share.show');

Route::get('/oauth2/facebook/redirect', function (Request $request) {
    $to = $request->string('redirect')->toString();

    if (is_string($to) && str_starts_with($to, '/') && ! str_starts_with($to, '//') && ! str_contains($to, '\\')) {
        $request->session()->put('url.intended', $to);
    }

    return Socialite::driver('facebook')->redirect();
});

Route::get('/oauth2/facebook', [UserController::class, 'storeFacebook']);
