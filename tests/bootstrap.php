<?php

// The local desktop session exports APP_ENV, SESSION_DRIVER, DB_DATABASE, etc.
// globally. Laravel's env repository reads $_SERVER before $_ENV, and PHPUnit
// only populates $_ENV/getenv, so those stale values would win. Sync them so
// the values declared in phpunit.xml take effect.
foreach ($_ENV as $key => $value) {
    if (array_key_exists($key, $_SERVER)) {
        $_SERVER[$key] = $value;
    }
}

require __DIR__.'/../vendor/autoload.php';
