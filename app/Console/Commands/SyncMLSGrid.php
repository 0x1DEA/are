<?php

namespace App\Console\Commands;

use App\Models\Listing;
use App\Models\ListingMedia;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Uri;
use JsonMachine\Items;
use JsonMachine\JsonDecoder\ExtJsonDecoder;

#[Signature('mlsgrid:sync')]
#[Description('Replicates and updates MLS data via MLSGRID API')]
class SyncMLSGrid extends Command
{
    private const int PAGE_SIZE = 1000;

    private const int LISTING_UPSERT_CHUNK = 100;

    private const int MEDIA_UPSERT_CHUNK = 500;

    private const int REFRESH_LISTINGS_PER_RUN = 50;

    private const string API_DEMO = 'https://api-demo.mlsgrid.com/v2/';

    private const string API = 'https://api.mlsgrid.com/v2/';

    private const string LOCK_REPLICATE = 'mls_replicate.lock';

    private const string LOCK_UPDATE = 'mls_update.lock';

    private const string MLS = 'mred';

    /**
     * @throws ConnectionException
     */
    public function handle(): int
    {
        // mitigate rapid memory inflation for our many large upserts
        DB::disableQueryLog();

        // we have two progress files: mls_replicate.lock and mls_update.lock
        // replication is the initial data acquisition and update is the periodic ongoing mirroring of mls data
        // $status holds either a resume timestamp or 'done' for the replication lock file
        $status = Storage::disk('local')->get(self::LOCK_REPLICATE);

        $initial = true;

        if ($status === 'done') {
            // we've done our initial import already
            $initial = false;

            $this->info('Initial replication complete.');

            // get our last updated timestamp
            $status = Storage::disk('local')->get(self::LOCK_UPDATE);

            // we should always set this at the end of any run, even during the runs
            // if this is null then something went very wrong
            if ($status === null) {
                $this->error('Replication marked complete but could not find latest timestamp lock');

                return self::FAILURE;
            }

            $this->info("Starting update operation from {$status}");
        } else {
            // if status is null we have not started replication
            // if status is a timestamp then are resuming replication
            // either way we are doing initial replication
            $this->info($status === null ? 'Starting initial replication' : "Resuming initial replication from {$status}");
        }

        $next = null;
        $last_timestamp = null;
        $listings = [];
        $media = [];

        do {
            $mls = self::MLS;

            // Our oData query
            $filter = "OriginatingSystemName eq '{$mls}' and StandardStatus eq 'Active'";

            // Only grab displayable listing on initial replication
            if ($initial) {
                $filter .= ' and MlgCanView eq true';
            }

            // If we are resuming a replication or doing an update
            // During updates replications we follow the links instead of updating
            // Only first run of an update or first run of a replication resume
            if ((! $initial || $status !== null) && ! $next) {
                $filter .= " and ModificationTimestamp gt {$status}";
            }

            // Follow the next link, if null (first request of run) fill in using our query
            $url = $next ?? Uri::of((App::isProduction() ? self::API : self::API_DEMO).'Property')->withQuery([
                '$filter' => $filter,
                '$expand' => 'Media,Rooms,UnitTypes',
                '$skip' => 0,
                '$top' => self::PAGE_SIZE,
            ])->toString();

            $res = Http::withHeaders([
                'Accept-Encoding' => 'gzip',
                'Authorization' => 'Bearer '.config('services.mlsgrid.key'),
            ])->get($url)->resource();

            // Store our next page link, null if missing (important for later checks)
            // Other condition use $next being null to indicate first run. we cant do that after this point
            // the loop will exit if this is null after this point
            $next = iterator_to_array(Items::fromStream($res, ['pointer' => '/@odata.nextLink']))['@odata.nextLink'] ?? null;
            rewind($res);
            $items = Items::fromStream($res, ['pointer' => '/value', 'decoder' => new ExtJsonDecoder(true)]);

            foreach ($items as $item) {
                $listings[] = Listing::arrayFromAPI($item);

                if (($count = count($listings)) >= self::LISTING_UPSERT_CHUNK) {
                    $this->upsert($listings, count: $count);
                }

                if (array_key_exists('Media', $item)) {
                    for ($j = 0; $j < count($item['Media']); $j++) {
                        $media[] = ListingMedia::arrayFromAPI($item['Media'][$j]);

                        if (($count = count($media)) >= self::MEDIA_UPSERT_CHUNK) {
                            $this->upsert($media, true, $count);
                        }
                    }
                }

                $last_timestamp = $item['ModificationTimestamp'];
            }

            unset($res);
            gc_collect_cycles();

            if ($listings) {
                $this->upsert($listings);
            }
            if ($media) {
                $this->upsert($media, true);
            }

            // increment replication progress
            if ($last_timestamp !== null) {
                Storage::disk('local')->put($initial ? self::LOCK_REPLICATE : self::LOCK_UPDATE, $last_timestamp);
            }

            $this->line('Peak: '.round(memory_get_peak_usage(true) / 1048576).'MB');
        } while ($next);

        if ($initial) {
            // initial replication is done
            Storage::disk('local')->put(self::LOCK_REPLICATE, 'done');
            // we save our last timestamp in the update lock too so we know where to update from
            Storage::disk('local')->put(self::LOCK_UPDATE, $last_timestamp);
        }

        $this->info('Processed records.');

        return self::SUCCESS;
    }

    private function upsert(array &$data, bool $media = false, ?int $count = null): void
    {
        if ($count === null) {
            $count = count($data);
        }

        $this->info("Upserting {$count} ".($media ? 'media' : 'listing').' records...');

        if ($media) {
            //            ListingMedia::upsert($data, 'key');
        } else {
            //            Listing::upsert($data, 'mls_id');
        }

        $data = [];

        if ($media) {
            // dispatch downloader. it only has ~1hr and 1 req/hr to download each image
            //            Artisan::queue(DownloadListingMedia::class, ['--limit' => self::MEDIA_UPSERT_CHUNK]);
        } else {
            // get coordinates for listing, beware of api limits
            //            Artisan::queue(GeocodeListings::class);
        }
    }
}
