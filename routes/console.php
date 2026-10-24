<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();

Schedule::command('app:post-certificate-command')->hourly();
Schedule::command('app:set-url-certificate-command')->hourlyAt(30);
// Sisa sesi impor .mbz bisa ratusan MB per berkas — sapu yang ditinggalkan.
Schedule::command('mbz:purge-temp')->dailyAt('03:15');
Schedule::command('db:backup')->daily();
Schedule::command('db:backup --weekly')->weekly();
