<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

// Runs against a new disposable MySQL database, never the application's tables.
require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (! $app->environment('local', 'testing') || config('database.default') !== 'mysql') {
    fwrite(STDERR, "Run this script only in local/testing with MySQL and CREATE DATABASE permission.\n");
    exit(1);
}
$finder = new ExecutableFinder;
$age = getenv('TEST_AGE_BINARY') ?: $finder->find('age');
if (! $age || ! is_executable($age) || ! is_executable(dirname($age).'/age-keygen') || ! $finder->find('mysql') || ! $finder->find('mysqldump')) {
    fwrite(STDERR, "Install age, age-keygen and MySQL client tools; optionally set TEST_AGE_BINARY.\n");
    exit(1);
}
$name = 'gapura_security_test_'.bin2hex(random_bytes(6));
$file = sys_get_temp_dir().'/'.$name.'.xml';
$exit = 1;
DB::statement('CREATE DATABASE `'.$name.'`');
try {
    $xml = simplexml_load_file(base_path('phpunit.xml'));
    $xml['bootstrap'] = base_path('tests/bootstrap.php');
    foreach ($xml->testsuites->testsuite as $suite) {
        $suite->directory = base_path((string) $suite->directory);
    }
    $xml->source->include->directory = app_path();
    foreach ($xml->php->env as $entry) {
        if ((string) $entry['name'] === 'DB_DATABASE') {
            $entry['value'] = $name;
        }
    }
    $xml->asXML($file);
    chmod($file, 0600);
    $args = [PHP_BINARY, 'vendor/bin/phpunit', '--configuration', $file, '--colors=never'];
    if (! in_array('--all', $argv, true)) {
        array_push($args, '--filter', 'LogBackupTest|AuthenticationTest|ApiAuthenticationTest|AuthControllerTest');
    }
    $process = new Process($args, base_path(), ['TEST_AGE_BINARY' => $age, 'TEST_MYSQL_RESTORE' => '1']);
    $process->setTimeout(300);
    $exit = $process->run(fn ($type, $buffer) => print ($buffer));
} finally {
    DB::statement('DROP DATABASE `'.$name.'`');
    if (is_file($file)) {
        unlink($file);
    }
}
exit($exit);
