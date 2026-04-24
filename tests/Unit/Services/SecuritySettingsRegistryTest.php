<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

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
