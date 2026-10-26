<?php

namespace App\Console\Commands;

use App\Models\ListingMedia;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\Batch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

#[Signature('listing:media {listing?} {--max-runs=-1} {--limit=none}')]
#[Description('Downloads listing media')]
class DownloadListingMedia extends Command
{
    private const int URL_TTL = 55;

    private const string DIR = 'listing_media';

    private const int BAR_ADVANCE = -1;

    private const int BAR_FAILED = -2;

    private const int BAR_FINISH = -3;

    private const int BAR_LOG = -4;

    private ?array $console = [
        'enabled' => false,
    ];

    public function setupFilesystem(): void
    {
        if (Storage::disk('public')->directoryMissing(self::DIR)) {
            Storage::disk('public')->makeDirectory(self::DIR);
        }
    }

    public function handle(): void
    {
        $runs = 0;

        $query = ListingMedia::query();

        if ($this->option('limit') !== 'none') {
            $query->limit($this->option('limit'));
        }

        $this->initConsole();

        $query->whereNull('downloaded_at')
            // Lead time of 5 min for processing
            ->where('mls_updated_at', '>', now()->subMinutes(self::URL_TTL))
            // Retry media only when refreshed (we can only request new URLs after an hour)
            ->where('mls_updated_at', '>', 'failed_at')
            ->orderByDesc('order') // get thumbnails for each first
            ->orderBy('mls_updated_at'); // get soonest expiring first

        // If specified, only grab media for a specified listing
        if ($this->argument('listing')) {
            $query->where('mls_listing_id', $this->argument('listing'));
        }

        $query->chunkById(200, function (Collection $media) use (&$runs) {
            // Increment run counter and also limit by command option
            if ($runs++ > $this->option('max-runs') && $this->option('max-runs') != '-1') {
                $this->info('Processed maximum chunks '.$this->option('max-runs'));

                return false;
            }

            // Reset progress bar for this chunk
            $this->msg('Processing chunk of '.$media->count(), $media->count());

            // set up directory if missing
            $this->setupFilesystem();

            $this->sendRequests($media->keyBy('key'));

            return true;
        });

        if ($runs === 0) {
            $this->info('Nothing to do.');
        }

        $expired = ListingMedia::query()
            ->whereNull('downloaded_at')
            ->where(function (Builder $query) {
                $query->where('mls_updated_at', '<', now()->subMinutes(self::URL_TTL))
                    ->orWhere('mls_updated_at', '<', 'failed_at');
            })->count();

        if ($expired > 0) {
            $this->warn("{$expired} record".($expired === 1 ? ' is' : 's are').' missing but expired!');
        }

        $this->info('Done');
    }

    public function sendRequests($media): void
    {
        $updates = [];

        // ignore, upsert defaults, won't be used
        $ignoredDefaults = [
            'mls_listing_id' => '',
            'source_url' => '',
            'height' => 0,
            'width' => 0,
        ];

        // MLS Grid requires we authenticate media requests with our key in the user agent
        Http::withHeaders([
            'User-Agent' => config('services.mlsgrid.key'),
        ])->batch(function (Batch $batch) use (&$media, &$updates, $ignoredDefaults) {
            foreach ($media as $m) {
                // get extension from media url
                parse_str(parse_url($m->source_url, PHP_URL_PATH), $query);
                $filename = $m->key.'.'.Str::afterLast($query['id'], '.');

                // if we already have this file no need to download again, update the DB (early testing fix)
                if (Storage::disk('public')->exists(self::DIR.'/'.$filename)) {
                    $updates[] = [
                        'id' => $m->id,
                        'key' => $m->key,
                        'url' => self::DIR.'/'.$filename,
                        'downloaded_at' => now(),
                        'bytes' => Storage::disk('public')->size(self::DIR.'/'.$filename),
                        'failed_at' => null,
                        ...$ignoredDefaults,
                    ];
                } else {
                    // make the key the $filename for later
                    $batch->as($filename)
                        ->sink(Storage::disk('public')->path(self::DIR.'/'.$filename))
                        ->get($m->source_url);
                }
            }
            $this->msg('Batched '.($media->count() - count($updates)).' to download. '.count($updates).' cached.');
        })->progress(function (Batch $batch, int|string $filename) use (&$updates, &$media, $ignoredDefaults) {
            // $filename is the key is the full filename (no path)
            $media_key = Str::beforeLast($filename, '.');
            $updates[] = [
                'id' => $media->get($media_key)->id,
                'key' => $media_key,
                'url' => self::DIR.'/'.$filename,
                'downloaded_at' => now(),
                'bytes' => Storage::disk('public')->size(self::DIR.'/'.$filename),
                'failed_at' => null,
                ...$ignoredDefaults,
            ];
            $this->msg("Downloading {$media_key}...", self::BAR_ADVANCE);
        })->catch(function (Batch $batch, int|string $filename) use ($ignoredDefaults, &$updates, &$media) {
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
            Storage::disk('public')->delete(self::DIR.'/'.$filename);
            $this->msg('Failed to download media: '.$filename, self::BAR_FAILED);
        })->concurrency(3)->send();

        $this->msg('Chunk complete, upserting '.count($updates), self::BAR_FINISH);

        ListingMedia::upsert($updates, 'id', ['key', 'url', 'downloaded_at', 'bytes', 'failed_at']);

    }

    public function msg(string $msg, int $mode = self::BAR_LOG): void
    {
        if (! $this->console['enabled']) {
            return;
        }

        if ($mode === self::BAR_ADVANCE) {
            $this->console['bar']->advance();
            $this->console['bar']->setMessage($msg);
        } else {
            $this->console['log']->writeLn($msg);

            if ($mode === self::BAR_FINISH) {
                $this->console['bar']->finish();
            } elseif ($mode > 0) {
                // bar reset
                $this->console['bar']->setProgress(0);
                $this->console['bar']->setMaxSteps($mode);
            }
        }
    }

    public function initConsole(): void
    {
        $output = $this->output->getOutput();
        if ($output instanceof ConsoleOutput) {
            $this->console['log'] = $output->section();
            $this->console['progress'] = $output->section();

            ProgressBar::setFormatDefinition('media-download', "\n%message%\n%current%/%max% %bar% %percent%%");
            $this->console['bar'] = new ProgressBar($this->console['progress'], 1);
            $this->console['bar']->setFormat('media-download');
            $this->console['bar']->setMessage('');
            $this->console['bar']->start();
        }
    }
}
