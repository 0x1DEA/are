<?php

namespace App\Jobs;

use App\Models\ListingMedia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\Batch;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\GuzzleRateLimiterMiddleware\RateLimiterMiddleware;

class DownloadListingMedia implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public array $listings = [])
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $media = ListingMedia::query()
            ->whereNull('downloaded_at')
//            ->limit(10)
            ->whereIn('mls_listing_id', $this->listings)
            ->get()
            ->keyBy('key');

        $updates = [];

        $dir = 'listing_media';

        $path = 'app/public/'.$dir.'/';

        if (Storage::disk('public')->directoryMissing($dir)) {
            Storage::disk('public')->makeDirectory($dir);
        }

        $responses = Http::withHeaders([
            'User-Agent' => config('services.mlsgrid.key'),
        ])->withMiddleware(RateLimiterMiddleware::perSecond(1))
            ->batch(function (Batch $batch) use (&$media, $path) {
                foreach ($media as $key => $m) {
                    parse_str(parse_url($m->source_url, PHP_URL_PATH), $query);
                    $ext = Str::after($query['id'], '.');

                    $batch->as($key.'.'.$ext)
                        ->sink(storage_path($path.$key.'.'.$ext))
                        ->get($m->source_url);
                }
            })->progress(function (Batch $batch, int|string $key, Response $response) use (&$updates, $dir) {
                $updates[] = [
                    'key' => Str::beforeLast($key, '.'),
                    'url' => $dir.$key,
                    'downloaded_at' => now(),
                    // ignore, upsert defaults, won't be used
                    'mls_listing_id' => '',
                    'source_url' => '',
                    'height' => 0,
                    'width' => 0,
                ];
            })->catch(function (Batch $batch, int|string $key, mixed $response) {
                Log::error('Failed to download media: '.$key);
            })->finally(function () use ($updates) {
                ListingMedia::upsert($updates, 'key', ['key', 'url', 'downloaded_at']);
            })->concurrency(1)->send();

//        ListingMedia::upsert($updates, 'key', ['key', 'url', 'downloaded_at']);

//        dd($responses, $updates);
    }
}
