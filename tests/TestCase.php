<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    /**
     * RefreshDatabase only resets the SQL connection (SQLite :memory: here) —
     * it never touches cache stores. Two things bleed across tests/files in
     * the same PHPUnit process without this:
     *   - Rate limiter hits (ThrottleRequests reads the default 'array' store,
     *     which persists for the process lifetime) — a test that intentionally
     *     trips a throttle (e.g. RateLimitTest) can 429 an unrelated test that
     *     runs afterward.
     *   - RedirectCacheService, which caches into the real 'redis' store
     *     (not the default store) regardless of CACHE_STORE — a redirect
     *     created in one test can be served stale to the next, pointing at a
     *     row ID that no longer exists in that test's fresh database.
     *
     * See tests/bootstrap.php for why env()/config() resolution needed a
     * separate fix (dotenv loading the real .env over phpunit.xml's forced
     * values) — that one was silently sending RefreshDatabase against the
     * real Postgres dev database.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        try {
            Cache::store('redis')->flush();
        } catch (\Throwable) {
            // Redis unreachable in this environment — nothing to flush.
        }
    }
}
