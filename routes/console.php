<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:post-certificate-command')->hourly();
Schedule::command('app:set-url-certificate-command')->hourlyAt(30);

// Backups — off-peak (Asia/Jakarta app timezone)
Schedule::command('logs:backup')->dailyAt('01:30');
Schedule::command('db:backup')->dailyAt('02:00');
Schedule::command('db:backup --weekly')->weeklyOn(1, '03:00'); // Monday 03:00
