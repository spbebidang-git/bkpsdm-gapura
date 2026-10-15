<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Illuminate\Log\LogManager;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as Monolog;

class SecureLogManager extends LogManager
{
    public function build(array $config)
    {
        $config['permission'] = 0600;
        $config['locking'] = true;
        $logger = parent::build($config);
        app(SecureLogs::class)($logger);

        return $logger;
    }

    protected function createEmergencyLogger()
    {
        // Laravel's default fallback bypasses channel taps. It must also redact.
        $handler = new StreamHandler(
            config('logging.channels.emergency.path', storage_path('logs/laravel.log')),
            Level::Debug, true, 0600, true
        );
        $logger = new Logger(new Monolog('emergency', [$handler]), $this->app['events']);
        app(SecureLogs::class)($logger);

        return $logger;
    }
}
