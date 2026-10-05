<?php

use App\Console\Commands\SyncMLSGrid;
use Illuminate\Support\Facades\Schedule;

Schedule::command(SyncMLSGrid::class)->dailyAt('07:00');
