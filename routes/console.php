<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\AttendanceChallenge;
use App\Models\SchoolImportBatch;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('attendance:mark-absent')->dailyAt('23:59')->withoutOverlapping();
Schedule::command('payments:expire-overdue')->dailyAt('00:05')->withoutOverlapping();
Schedule::call(fn () => AttendanceChallenge::query()->where('expires_at', '<=', now())->delete())
    ->hourly()->name('attendance:prune-expired-challenges')->withoutOverlapping();
Schedule::call(fn () => SchoolImportBatch::query()->where('expires_at', '<=', now())->update([
    'payload' => '',
    'password_export' => null,
]))
    ->hourly()->name('school-imports:prune-expired-staging')->withoutOverlapping();
