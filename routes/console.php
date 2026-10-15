<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:post-certificate-command')->hourly();
Schedule::command('app:set-url-certificate-command')->hourlyAt(30);

// Backups — off-peak (Asia/Jakarta app timezone)
Schedule::command('logs:backup')->dailyAt('01:30')->timezone('Asia/Jakarta')->withoutOverlapping(120)->onOneServer();
Schedule::command('db:backup')->dailyAt('02:00')->timezone('Asia/Jakarta')->withoutOverlapping(120)->onOneServer();
Schedule::command('db:backup --weekly')->weeklyOn(1, '03:00')->timezone('Asia/Jakarta')->withoutOverlapping(120)->onOneServer(); // Monday 03:00
Schedule::command('kedaton:sync-opds')->dailyAt('01:00')->timezone('Asia/Jakarta');
Schedule::command('participant:notify-deadlines')->everyMinute()->withoutOverlapping();
Schedule::command('participant:prune-notifications')->dailyAt('02:30')->timezone('Asia/Jakarta')->withoutOverlapping();

Schedule::command('files:backup')->dailyAt('03:45')->timezone('Asia/Jakarta')->withoutOverlapping(120)->onOneServer();
Schedule::command('backup:health')->hourlyAt(15)->withoutOverlapping(30)->onOneServer();
