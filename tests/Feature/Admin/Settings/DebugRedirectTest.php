<?php
namespace Tests\Feature\Admin\Settings;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
class DebugRedirectTest extends TestCase
{
    use RefreshDatabase;
    public function test_debug_route(): void
    {
        echo "\n\nroute URL: " . route('admin.settings.plugins.index') . "\n";
        echo "config admin.admin_url: " . config('admin.admin_url') . "\n";
        echo "env ADMIN_URL: " . var_export(env('ADMIN_URL'), true) . "\n";
        echo "env INSTALLED: " . var_export(env('INSTALLED'), true) . "\n\n";
        $this->assertTrue(true);
    }
}
