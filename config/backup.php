<?php

return [
    // Only public age recipients belong on the application server.
    'recipients' => array_values(array_filter(array_map('trim', explode(',', env('BACKUP_AGE_RECIPIENTS', ''))))),
    'age_binary' => env('BACKUP_AGE_BINARY', 'age'),
    'root' => storage_path('app/backups'),
    'remote_disk' => env('BACKUP_REMOTE_DISK'),
    'remote_prefix' => env('BACKUP_REMOTE_PREFIX', 'gapura'),
    'require_remote' => env('BACKUP_REQUIRE_REMOTE', false),
    'daily_keep' => (int) env('BACKUP_DAILY_KEEP', 7),
    'weekly_keep' => (int) env('BACKUP_WEEKLY_KEEP', 4),
    'log_keep' => (int) env('LOG_BACKUP_KEEP', 30),
    'files_keep' => (int) env('BACKUP_FILES_KEEP', 7),
    'file_roots' => [storage_path('app/public'), public_path('assets/media'), base_path('media')],
    'timeout' => (int) env('BACKUP_TIMEOUT', 3600),
    'alert_email' => env('BACKUP_ALERT_EMAIL'),
];
