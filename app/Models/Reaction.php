<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reaction extends Model
{

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'listing_id', 'mls_id');
    }
}
