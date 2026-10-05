<?php

namespace App\Console\Commands;

use App\Models\Listing;
use App\Models\ListingMedia;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Uri;

#[Signature('mlsgrid:sync')]
#[Description('Replicates and updates MLS data via MLSGRID API')]
class SyncMLSGrid extends Command
{
    private const int PAGE_SIZE = 200;

    private const int LISTING_UPSERT_CHUNK = 100;

    private const int MEDIA_UPSERT_CHUNK = 500;

    private const int MEDIA_JOB_SIZE = 25;

    private const string MEDIA_QUEUE = 'media';

    private const int MAX_QUEUED_MEDIA_JOBS = 400;

    private const int REFRESH_LISTINGS_PER_RUN = 50;

    private const string API = 'https://api-demo.mlsgrid.com/v2/';

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

        do {
            $mls = self::MLS;

            // Our oData query
            $filter = "OriginatingSystemName eq '{$mls}'";

            // Only grab displayable listing on initial replication
            if ($initial) {
                $filter .= ' and MlgCanView eq true';
            }

            // If we are resuming a replication or doing an update
            // During updates replications we follow the links instead of updating
            // Only first run of an update or first run of a replication resume
            if ((! $initial || $status === '') && ! $next) {
                $filter .= " and ModificationTimestamp gt {$status}";
            }

            // Follow the next link, if null (first request of run) fill in using our query
            $url = $next ?? Uri::of(self::API.'Property')->withQuery([
                '$filter' => $filter,
                '$expand' => 'Media,Rooms,UnitTypes',
                '$skip' => 0,
                '$top' => self::PAGE_SIZE,
            ])->toString();

            $res = Http::withHeaders([
                'Accept-Encoding' => 'gzip',
                'Authorization' => 'Bearer '.config('services.mlsgrid.key'),
            ])->get($url)->json();

            // Store our next page link, null if missing (important for later checks)
            // Other condition use $next being null to indicate first run. we cant do that after this point
            // the loop will exit if this is null after this point
            $next = $res['@odata.nextLink'] ?? null;
            $res = $res['value'];

            $listings = [];
            $media = [];

            for ($i = 0; $i < count($res); $i++) {
                $listings[] = Listing::arrayFromAPI($res[$i]);

                if (($count = count($listings)) >= self::LISTING_UPSERT_CHUNK) {
                    $this->upsert($listings, count: $count);
                }

                $last_timestamp = $res[$i]['ModificationTimestamp'];

                if (array_key_exists('Media', $res[$i])) {
                    for ($j = 0; $j < count($res[$i]['Media']); $j++) {
                        $media[] = ListingMedia::arrayFromAPI($res[$i]['Media'][$j]);

                        if (($count = count($media)) >= self::MEDIA_UPSERT_CHUNK) {
                            $this->upsert($media, true, $count);
                        }
                    }
                }
            }

            // increment replication progress
            Storage::disk('local')->put($initial ? self::LOCK_REPLICATE : self::LOCK_UPDATE, $last_timestamp);
        } while ($next);

        $this->upsert($listings);
        $this->upsert($media, true);

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
            ListingMedia::upsert($data, 'key');
        } else {
            Listing::upsert($data, 'mls_id');
        }

        // reset our array and garbage collect to make sure our memory stays low
        $data = [];
        gc_collect_cycles();

        if ($media) {
            // dispatch downloader. it only has ~1hr and 1 req/hr to download each image
            Artisan::queue(DownloadListingMedia::class, ['--limit' => self::MEDIA_UPSERT_CHUNK]);
        } else {
            // get coordinates for listing, beware of api limits
            Artisan::queue(GeocodeListings::class);
        }
    }
}
