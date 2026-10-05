<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ListingMedia extends Model
{
    protected $casts = [
        'mls_modified_at' => 'datetime',
        'mls_fetched_at' => 'datetime',
        'failed_at' => 'datetime',
        'downloaded_at' => 'datetime',
    ];

    protected $guarded = [];

    public static function arrayFromAPI(array $m): array
    {
        return [
            'key' => $m['MediaKey'],
            'mls_listing_id' => $m['ResourceRecordID'],
            'source_url' => $m['MediaURL'],

            'type' => $m['MediaObjectID'] ?? null,
            'order' => $m['Order'] ?? null,

            'width' => $m['ImageWidth'],
            'height' => $m['ImageHeight'],

            'mls_modified_at' => $m['MediaModificationTimestamp'] ? Carbon::make($m['MediaModificationTimestamp']) : null,
            'mls_updated_at' => now(),
        ];
    }
}
