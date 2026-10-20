<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled Commands
|--------------------------------------------------------------------------
| App/backup jobs run off-peak in Asia/Jakarta (the app timezone).
| Long-running or backup tasks use withoutOverlapping + onOneServer so they
| never double-run across servers or overlap themselves.
|
|   Certificate — issue + refresh signed URL
|   Backups      — logs, db (daily + weekly), files, health check
|   Sync         — Kedaton OPDs
|   Participant  — deadline notifications + notification pruning
*/

// Certificate
Schedule::command('app:post-certificate-command')->hourly();
Schedule::command('app:set-url-certificate-command')->hourlyAt(30);

// Backups — off-peak
Schedule::command('logs:backup')->dailyAt('01:30')->timezone('Asia/Jakarta')->withoutOverlapping(120)->onOneServer();
Schedule::command('db:backup')->dailyAt('02:00')->timezone('Asia/Jakarta')->withoutOverlapping(120)->onOneServer();
Schedule::command('db:backup --weekly')->weeklyOn(1, '03:00')->timezone('Asia/Jakarta')->withoutOverlapping(120)->onOneServer(); // Monday 03:00
Schedule::command('files:backup')->dailyAt('03:45')->timezone('Asia/Jakarta')->withoutOverlapping(120)->onOneServer();
Schedule::command('backup:health')->hourlyAt(15)->withoutOverlapping(30)->onOneServer();

// Sync
Schedule::command('kedaton:sync-opds')->dailyAt('01:00')->timezone('Asia/Jakarta');

// Participant
Schedule::command('participant:notify-deadlines')->everyMinute()->withoutOverlapping();
Schedule::command('participant:prune-notifications')->dailyAt('02:30')->timezone('Asia/Jakarta')->withoutOverlapping();
