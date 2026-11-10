<?php

use App\Console\Commands\OptimizeListingMedia;
use App\Console\Commands\SyncMLSGrid;
use Illuminate\Support\Facades\Schedule;

Schedule::command(SyncMLSGrid::class)->dailyAt('07:00');
Schedule::command(OptimizeListingMedia::class)->hourly();
