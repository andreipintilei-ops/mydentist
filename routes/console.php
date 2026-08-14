<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly: recompute the city × service counts, then regenerate the sitemaps so
// lastmod and the thin-content guard stay current. Order matters — the sitemap
// reads the freshly-rebuilt stats.
Schedule::command('stats:rebuild-city-service')->dailyAt('03:00');
Schedule::command('sitemap:generate')->dailyAt('03:10');
