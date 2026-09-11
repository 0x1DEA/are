<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListingMedia extends Model
{
    protected $casts = [
        'mls_modified_at' => 'datetime',
        'downloaded_at' => 'datetime',
    ];

    protected $guarded = [];
}
