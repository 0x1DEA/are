<?php

namespace App\Console\Commands;

use App\Models\Listing;
use Geocodio\Geocodio;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('listing:geocode')]
#[Description('Geocodes listing(s) using the Geocodio API')]
class GeocodeListings extends Command
{
    public function handle(): void
    {
        $geocoder = new Geocodio;
        $geocoder->setApiKey(config('services.geocodio.key'));

        $listings = Listing::query()
            ->limit(500)
            ->whereNull('geo_fetched_at')
            ->select([
                'mls_id',
                'address_number',
                'address_direction',
                'address_street',
                'address_street_suffix',
                'address_unit',
                'address_city',
                'address_state',
                'address_postal',
            ])
            ->get()
            ->mapWithKeys(function (Listing $l) {
                $street = implode(' ', array_filter([
                    $l->address_number,
                    $l->address_direction,
                    $l->address_street,
                    $l->address_street_suffix,
                ], fn ($value) => $value !== null));

                return [
                    $l->mls_id => [
                        'street' => $street,
                        'city' => $l->address_city,
                        'state_province' => $l->address_state,
                        'postal_code' => $l->address_postal,
                        'country' => 'USA',
                    ],
                ];
            });

        if ($listings->isEmpty()) {
            return;
        }

        $updates = [];

        $results = $geocoder->geocode($listings->toArray())['results'];

        foreach ($results as $mls_id => $result) {
            $coordinates = null;

            foreach ($result['response']['results'] as $address) {
                if ($address['accuracy'] === 1) {
                    $coordinates = DB::raw("ST_GeomFromText('POINT({$address['location']['lat']} {$address['location']['lng']})', 4326)");
                }
            }

            $updates[] = [
                'mls_id' => $mls_id,
                'coordinates' => $coordinates,
                'geo_data' => json_encode($result),
                'geo_fetched_at' => now(),
            ];
        }

        Listing::upsert($updates, 'mls_id', ['coordinates', 'geo_data', 'geo_fetched_at']);
    }
}
