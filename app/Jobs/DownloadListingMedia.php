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
use JetBrains\PhpStorm\NoReturn;
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
    #[NoReturn]
    public function handle(bool $eat = false): void
    {
        $media = ListingMedia::query()->whereNull('downloaded_at');

        if ($eat) {
            $media->whereIn('mls_listing_id', $this->listings);
        } else {
            $media->limit(25);
        }

        $media = $media->get()->keyBy('key');

        $updates = [];

        $dir = 'listing_media';

        $path = 'app/public/'.$dir.'/';

        if (Storage::disk('public')->directoryMissing($dir)) {
            Storage::disk('public')->makeDirectory($dir);
        }

        $responses = Http::withHeaders([
            'User-Agent' => config('services.mlsgrid.key'),
        ])->withMiddleware(RateLimiterMiddleware::perSecond(1))
            ->batch(function (Batch $batch) use (&$media, $path, $dir, &$updates) {
                foreach ($media as $m) {
                    parse_str(parse_url($m->source_url, PHP_URL_PATH), $query);
                    $ext = Str::after($query['id'], '.');

                    // If we already have this file no need to download again, update the DB (early testing fix)
                    if (Storage::disk('public')->exists($dir.'/'.$m->key.'.'.$ext)) {
                        $updates[] = [
                            'id' => $m->id,
                            'key' => $m->key,
                            'url' => $dir.'/'.$m->key.'.'.$ext,
                            'downloaded_at' => now(),
                            // ignore, upsert defaults, won't be used
                            'mls_listing_id' => '',
                            'source_url' => '',
                            'height' => 0,
                            'width' => 0,
                        ];
                    } else {
                        // We make the key the $filename for later
                        $batch->as($m->key.'.'.$ext)
                            ->sink(storage_path($path.$m->key.'.'.$ext))
                            ->get($m->source_url);
                    }
                }
            })->progress(function (Batch $batch, int|string $filename, Response $response) use (&$updates, $dir, $media) {
                // $key is the full filename (no path)
                $media_key = Str::beforeLast($filename, '.');
                $updates[] = [
                    'id' => $media->get($media_key)->id,
                    'key' => $media_key,
                    'url' => $dir.'/'.$filename,
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
                ListingMedia::upsert($updates, 'id', ['key', 'url', 'downloaded_at']);
            })->concurrency(1)->send();

                dd($responses, $updates);
    }
}
