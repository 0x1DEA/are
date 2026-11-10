<?php

namespace App\Console\Commands;

use App\Models\ListingMedia;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Signature('listing:media:optimize {--limit=20}')]
#[Description('Converts listing media to AVIF')]
class OptimizeListingMedia extends Command
{
    const int QUALITY = 65;

    public function handle(): void
    {
        if (! (gd_info()['AVIF Support'] ?? false)) {
            $this->error('GD has no AVIF support');

            return;
        }

        $media = ListingMedia::query()
            ->whereLike('url', '%.jpeg')
            ->limit($this->option('max-runs'))
            ->orderByDesc('bytes')
            ->get();

        $updates = [];

        $media->map(function (ListingMedia $lm) use (&$updates) {
            $disk = Storage::disk('public');
            $path = $disk->path($lm->url);
            $jpeg = imagecreatefromjpeg($path);
            $newPath = Str::beforeLast($lm->url, '.').'.avif';

            if ($jpeg) {
                ob_start();
                $avif = imageavif($jpeg, quality: 80);
                $data = ob_get_clean();

                // Free memory
                imagedestroy($jpeg);

                if (! $data) {
                    // according to docs $avif could still be true but fail to output
                    $this->error('GD output buffer error');

                    return;
                }

                if ($avif) {
                    $disk->put($newPath, $data);

                    $updates[] = [
                        'id' => $lm->id,
                        'url' => $newPath,
                        'key' => $lm->key,
                        'mls_listing_id' => $lm->mls_listing_id,
                        'bytes' => $disk->size($newPath),
                        'source_url' => $lm->source_url,
                        'height' => $lm->height,
                        'width' => $lm->width,
                    ];
                } else {
                    $this->error('Failed to convert to AVIF');

                    return;
                }
            } else {
                $this->error('Failed to create GD Image from JPEG');

                return;
            }
        });

        // save to db
        ListingMedia::upsert($updates, 'id');

        // delete JPEGs
        $media->map(function (ListingMedia $lm) {
            Storage::disk('public')->delete($lm->url);
        });
    }
}
