<?php

namespace Tests\Unit\Models;

use App\Models\Plugin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PluginModelTest extends TestCase
{
    use RefreshDatabase;

    private function createPlugin(array $overrides = []): Plugin
    {
        return Plugin::create(array_merge([
            'name' => 'Test Plugin',
            'package_name' => 'dixlase/test-plugin',
            'directory' => 'TestPlugin',
            'namespace' => 'Plugins\\TestPlugin',
            'slug' => 'test-plugin',
            'version' => '1.0.0',
            'author' => 'Test Author',
            'installed_at' => now(),
            'enabled_at' => now(),
        ], $overrides));
    }

    public function test_plugin_can_be_created(): void
    {
        $plugin = $this->createPlugin();

        $this->assertDatabaseHas('plugins', ['slug' => 'test-plugin']);
    }

    public function test_installed_at_is_cast_to_datetime(): void
    {
        $plugin = $this->createPlugin();

        $this->assertInstanceOf(\Carbon\Carbon::class, $plugin->installed_at);
    }

    public function test_is_installed(): void
    {
        $plugin = $this->createPlugin(['installed_at' => now()]);

        $this->assertTrue($plugin->isInstalled());
    }

    public function test_is_not_installed(): void
    {
        $plugin = $this->createPlugin(['installed_at' => null]);

        $this->assertFalse($plugin->isInstalled());
    }

    public function test_is_enabled(): void
    {
        $plugin = $this->createPlugin(['enabled_at' => now()]);

        $this->assertTrue($plugin->isEnabled());
    }

    public function test_is_not_enabled(): void
    {
        $plugin = $this->createPlugin(['enabled_at' => null]);

        $this->assertFalse($plugin->isEnabled());
    }

    public function test_has_update_available(): void
    {
        $plugin = $this->createPlugin([
            'version' => '1.0.0',
            'available_version' => '2.0.0',
        ]);

        $this->assertTrue($plugin->hasUpdateAvailable());
    }

    public function test_no_update_available(): void
    {
        $plugin = $this->createPlugin([
            'version' => '1.0.0',
            'available_version' => null,
        ]);

        $this->assertFalse($plugin->hasUpdateAvailable());
    }

    public function test_enabled_scope(): void
    {
        $this->createPlugin(['slug' => 'enabled-plugin', 'enabled_at' => now()]);
        $this->createPlugin(['slug' => 'disabled-plugin', 'enabled_at' => null]);

        $enabled = Plugin::enabled()->get();

        $this->assertEquals(1, $enabled->count());
        $this->assertEquals('enabled-plugin', $enabled->first()->slug);
    }

    public function test_installed_scope(): void
    {
        $this->createPlugin(['slug' => 'installed', 'installed_at' => now()]);
        $this->createPlugin(['slug' => 'not-installed', 'installed_at' => null]);

        $installed = Plugin::installed()->get();

        $this->assertEquals(1, $installed->count());
        $this->assertEquals('installed', $installed->first()->slug);
    }
}
