<?php

namespace App\Console\Commands;

use App\Models\ListingMedia;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\Batch;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

#[Signature('listing:media {--max-runs=-1} {--limit=none}')]
#[Description('Downloads listing media')]
class DownloadListingMedia extends Command
{
    private const int URL_TTL = 55;

    public function handle(): void
    {
        $runs = 0;

        $query = ListingMedia::query();

        if ($this->option('limit') !== 'none') {
            $query->limit($this->option('limit'));
        }

        $render = [
            'enabled' => true,
        ];

        $output = $this->output->getOutput();
        if (! $output instanceof ConsoleOutput) {
            $render['enabled'] = false;
        } else {
            $render['log'] = $output->section();
            $render['progress'] = $output->section();

            ProgressBar::setFormatDefinition('media-download', "\n%message%\n%current%/%max% %bar% %percent%%");
            $render['bar'] = new ProgressBar($render['progress'], 1);
            $render['bar']->setFormat('media-download');
            $render['bar']->setMessage('');
            $render['bar']->start();
        }

        $query->whereNull('downloaded_at')
            // Lead time of 5 min for processing
            ->where('mls_updated_at', '>', now()->subMinutes(self::URL_TTL))
            // Retry media only when refreshed (we can only request new URLs after an hour)
            ->where('mls_updated_at', '>', 'failed_at')
            ->orderByDesc('order') // get thumbnails for each first
//            ->orderBy('mls_updated_at') // get soonest expiring first
            ->chunkById(200, function (Collection $media) use (&$runs, &$render) {
                if ($runs++ > $this->option('max-runs') && $this->option('max-runs') != '-1') {
                    $this->info('Processed maximum chunks '.$this->option('max-runs'));

                    return false;
                }
                if ($render['enabled']) {
                    $render['bar']->setProgress(0);
                    $render['bar']->setMaxSteps($media->count());
                    $render['log']->writeln('Processing chunk of '.$media->count());
                }

                $media = $media->keyBy('key');

                $updates = [];

                // ignore, upsert defaults, won't be used
                $ignoredDefaults = [
                    'mls_listing_id' => '',
                    'source_url' => '',
                    'height' => 0,
                    'width' => 0,
                ];

                $dir = 'listing_media';

                // set up directory if missing
                if (Storage::disk('public')->directoryMissing($dir)) {
                    Storage::disk('public')->makeDirectory($dir);
                }

                Http::withHeaders([
                    // MLSGRID requires we authenticate media requests with our key like this
                    'User-Agent' => config('services.mlsgrid.key'),
                ])
//            ->withMiddleware(RateLimiterMiddleware::perSecond(1))
                    ->batch(function (Batch $batch) use (&$media, $dir, &$updates, $ignoredDefaults, &$render) {
                        foreach ($media as $m) {
                            // get extension from media url
                            parse_str(parse_url($m->source_url, PHP_URL_PATH), $query);
                            $filename = $m->key.'.'.Str::afterLast($query['id'], '.');

                            // if we already have this file no need to download again, update the DB (early testing fix)
                            if (Storage::disk('public')->exists($dir.'/'.$filename)) {
                                $updates[] = [
                                    'id' => $m->id,
                                    'key' => $m->key,
                                    'url' => $dir.'/'.$filename,
                                    'downloaded_at' => now(),
                                    'bytes' => Storage::disk('public')->size($dir.'/'.$filename),
                                    'failed_at' => null,
                                    ...$ignoredDefaults,
                                ];
                            } else {
                                // make the key the $filename for later
                                $batch->as($filename)
                                    ->sink(Storage::disk('public')->path($dir.'/'.$filename))
                                    ->get($m->source_url);
                            }
                        }

                        if ($render['enabled']) {
                            $render['log']->writeLn('Batched '.($media->count() - count($updates)).' to download. '.count($updates).' cached.');
                        }
                    })->progress(function (Batch $batch, int|string $filename, Response $response) use (&$updates, $dir, &$media, $ignoredDefaults, &$render) {
                        // $filename is the key is the full filename (no path)
                        $media_key = Str::beforeLast($filename, '.');
                        $updates[] = [
                            'id' => $media->get($media_key)->id,
                            'key' => $media_key,
                            'url' => $dir.'/'.$filename,
                            'downloaded_at' => now(),
                            'bytes' => Storage::disk('public')->size($dir.'/'.$filename),
                            'failed_at' => null,
                            ...$ignoredDefaults,
                        ];
                        if ($render['enabled']) {
                            $render['bar']->setMessage("Downloading {$media_key}...");
                            $render['bar']->advance();
                        }
                    })->catch(function (Batch $batch, int|string $filename, Response|RequestException|ConnectionException $response) use ($dir, $ignoredDefaults, &$updates, &$media, &$render) {
                        // $filename is key
                        $media_key = Str::beforeLast($filename, '.');
                        $updates[] = [
                            'id' => $media->get($media_key)->id,
                            'key' => $media_key,
                            'url' => null,
                            'downloaded_at' => null,
                            'bytes' => null,
                            'failed_at' => now(),
                            ...$ignoredDefaults,
                        ];
                        // delete errored body (sink copies eagerly so it's up to us to ask questions later)
                        // error body sizes are 18 bytes and 21 bytes
                        Storage::disk('public')->delete($dir.'/'.$filename);
                        if ($render['enabled']) {
                            $render['log']->writeLn('Failed to download media: '.$filename);
                            $render['bar']->advance();
                        }
                    })->concurrency(3)->send();

                if ($render['enabled']) {
                    $render['bar']->finish();
                    $render['log']->writeln('Chunk complete, upserting '.count($updates));
                }
                ListingMedia::upsert($updates, 'id', ['key', 'url', 'downloaded_at', 'bytes', 'failed_at']);
            });

        if ($runs === 0) {
            $expired = ListingMedia::query()
                ->whereNull('downloaded_at')
                ->where(function (Builder $query) {
                    $query->where('mls_updated_at', '<', now()->subMinutes(self::URL_TTL))
                        ->orWhere('mls_updated_at', '<', 'failed_at');
                })->count();

            $this->info('Nothing to do.');

            if ($expired > 0) {
                $this->warn("{$expired} record".($expired === 1 ? ' is' : 's are').' missing but expired!');
            }
        }
    }
}
