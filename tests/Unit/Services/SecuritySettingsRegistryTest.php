<?php

namespace Tests\Unit\Services;

use App\Services\SecuritySettingsRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecuritySettingsRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        SecuritySettingsRegistry::clearCache();
    }

    public function test_get_returns_default_for_missing(): void
    {
        $this->assertEquals('default', SecuritySettingsRegistry::get('nonexistent_key_xyz', 'default'));
    }

    public function test_get_categories_returns_array(): void
    {
        $categories = SecuritySettingsRegistry::getCategories();

        $this->assertIsArray($categories);
        $this->assertNotEmpty($categories);
    }

    public function test_export_returns_array(): void
    {
        $exported = SecuritySettingsRegistry::export();

        $this->assertIsArray($exported);
    }

    public function test_get_all_returns_array(): void
    {
        $all = SecuritySettingsRegistry::getAll();

        $this->assertIsArray($all);
    }

    public function test_get_all_grouped_returns_array(): void
    {
        $grouped = SecuritySettingsRegistry::getAllGrouped();

        $this->assertIsArray($grouped);
    }

    public function test_get_definition_returns_array_or_null(): void
    {
        $definition = SecuritySettingsRegistry::getDefinition();

        $this->assertTrue(is_array($definition) || is_null($definition));
    }

    public function test_clear_cache_does_not_throw(): void
    {
        SecuritySettingsRegistry::clearCache();

        $this->assertTrue(true);
    }
}
