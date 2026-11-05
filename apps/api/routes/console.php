<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('contracts:notify-expiring')->dailyAt('07:00');
Schedule::command('demo:reset')->dailyAt('03:00')->when(fn () => config('demo.enabled'));
