<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Filament\Pages\DeveloperPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeveloperPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_load_developer_page(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get(DeveloperPage::getUrl())->assertOk();
    }

    public function test_non_admin_is_forbidden(): void
    {
        $customer = User::factory()->create(['role' => UserRole::Customer]);

        $this->actingAs($customer)->get(DeveloperPage::getUrl())->assertForbidden();
    }

    public function test_mcp_config_exposes_url_only_no_api_key(): void
    {
        config(['app.url' => 'https://cacylinen.com']);

        $mcp = (new DeveloperPage)->getMcpConfig();

        $this->assertSame(['url' => 'https://mcp.cacylinen.com/mcp'], $mcp);
        $this->assertArrayNotHasKey('api_key', $mcp);
    }

    public function test_mcp_config_json_has_no_auth_header(): void
    {
        config(['app.url' => 'https://cacylinen.com']);

        $json = (new DeveloperPage)->getMcpConfigJson();
        $decoded = json_decode($json, true);

        $args = $decoded['mcpServers']['cacylinen-mcp']['args'];

        $this->assertSame([
            '-y',
            'mcp-remote',
            'https://mcp.cacylinen.com/mcp',
            '--transport',
            'http-only',
        ], $args);

        $this->assertStringNotContainsString('X-API-Key', $json);
        $this->assertStringNotContainsString('--header', $json);
    }

    public function test_internal_mcp_api_key_config_still_resolves_for_health_widget(): void
    {
        config(['services.mcp.api_key' => 'test-internal-key']);

        $this->assertSame('test-internal-key', config('services.mcp.api_key'));
    }
}
