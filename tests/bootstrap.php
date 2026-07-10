<?php

require __DIR__.'/../vendor/autoload.php';

/**
 * phpunit.xml's <php><env force="true"> block only calls putenv() — it never
 * populates $_ENV. This container's PHP CLI has `variables_order` without
 * 'E', so $_ENV starts empty regardless of the real OS environment. Laravel's
 * dotenv loader (Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables)
 * checks $_ENV/$_SERVER — not getenv() — to decide whether a variable is
 * "already set". Finding it unset, it loads the real .env file straight into
 * $_ENV, silently overriding every value phpunit.xml thinks it forced —
 * including DB_CONNECTION, which meant RefreshDatabase migrated against the
 * real Postgres dev database on every test run instead of SQLite in-memory.
 *
 * Setting $_ENV directly here, before Laravel ever bootstraps, makes dotenv's
 * "already set" check see these values and skip loading the real ones.
 */
$testEnv = [
    'APP_ENV'                => 'testing',
    'APP_MAINTENANCE_DRIVER' => 'file',
    'BCRYPT_ROUNDS'          => '4',
    'BROADCAST_CONNECTION'   => 'null',
    'CACHE_STORE'            => 'array',
    'DB_CONNECTION'          => 'sqlite',
    'DB_DATABASE'            => ':memory:',
    'DB_URL'                 => '',
    'MAIL_MAILER'            => 'array',
    'QUEUE_CONNECTION'       => 'sync',
    'SESSION_DRIVER'         => 'array',
    'SCOUT_DRIVER'           => 'null',
    'PULSE_ENABLED'          => 'false',
    'TELESCOPE_ENABLED'      => 'false',
    'NIGHTWATCH_ENABLED'     => 'false',
];

foreach ($testEnv as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key]    = $value;
    $_SERVER[$key] = $value;
}
