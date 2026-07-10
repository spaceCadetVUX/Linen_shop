<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Root always redirects to the default locale (permanent, SEO) — see
        // LocaleRoutingTest for the dedicated redirect coverage.
        $response = $this->get('/');

        $response->assertStatus(301);
    }
}
