<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('attendance:mark-absent')->dailyAt('23:59')->withoutOverlapping();
Schedule::command('payments:expire-overdue')->dailyAt('00:05')->withoutOverlapping();
