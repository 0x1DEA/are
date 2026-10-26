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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Uri;
use JsonMachine\Items;
use JsonMachine\JsonDecoder\ExtJsonDecoder;

#[Signature('mlsgrid:listing {mlsno}')]
#[Description('Fully refreshes a listing from MLSGRID')]
class SingleListing extends Command
{
    private const string API_DEMO = 'https://api-demo.mlsgrid.com/v2/';

    private const string API = 'https://api.mlsgrid.com/v2/';

    private const string MLS = 'mred';

    /**
     * @throws ConnectionException
     */
    public function handle(): int
    {
        $mls = self::MLS;
        $key = $this->argument('mlsno');

        $res = Http::withHeaders([
            'Accept-Encoding' => 'gzip',
            'Authorization' => 'Bearer '.config('services.mlsgrid.key'),
        ])->get(Uri::of((App::isProduction() ? self::API : self::API_DEMO).'Property')->withQuery([
            '$filter' => "OriginatingSystemName eq '{$mls}' and ListingId eq '{$key}'",
            '$expand' => 'Media,Rooms,UnitTypes',
        ]))->resource();

        // Store our next page link, null if missing (important for later checks)
        // Other condition use $next being null to indicate first run. we cant do that after this point
        // the loop will exit if this is null after this point
        $data = Items::fromStream($res, ['decoder' => new ExtJsonDecoder(true)]);

        foreach ($data as $key => $value) {
            if ($key === '@odata.nextLink') {
                continue;
            } elseif ($key === '@odata.context') {
                continue;
            } elseif ($key === 'value') {
                foreach ($value as $item) {
                    Listing::upsert([Listing::arrayFromAPI($item)], 'mls_id');

                    if (array_key_exists('Media', $item)) {
                        $media = [];

                        for ($j = 0; $j < count($item['Media']); $j++) {
                            $media[] = ListingMedia::arrayFromAPI($item['Media'][$j]);
                        }

                        ListingMedia::upsert($media, 'key');

                        Artisan::queue(DownloadListingMedia::class, [
                            'listing' => $item['ListingId'],
                        ]);
                    }
                }
            }
        }

        unset($res);
        gc_collect_cycles();

        $this->info('Processed record.');

        return self::SUCCESS;
    }
}
