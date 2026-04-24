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

namespace Tests\Unit\Models;

use App\Models\Theme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeModelTest extends TestCase
{
    use RefreshDatabase;

    private function createTheme(array $overrides = []): Theme
    {
        return Theme::create(array_merge([
            'name' => 'Test Theme',
            'package_name' => 'dixlase/test-theme',
            'directory' => 'TestTheme',
            'slug' => 'test-theme',
            'namespace' => 'Themes\\TestTheme',
            'version' => '1.0.0',
            'author' => 'Test Author',
            'installed_at' => now(),
            'config' => ['supports' => ['dark_mode', 'responsive']],
        ], $overrides));
    }

    public function test_theme_can_be_created(): void
    {
        $theme = $this->createTheme();

        $this->assertDatabaseHas('themes', ['slug' => 'test-theme']);
    }

    public function test_config_is_cast_to_array(): void
    {
        $theme = $this->createTheme();

        $theme->refresh();
        $this->assertIsArray($theme->config);
    }

    public function test_has_settings_is_cast_to_boolean(): void
    {
        $theme = $this->createTheme(['has_settings' => true]);

        $this->assertIsBool($theme->has_settings);
        $this->assertTrue($theme->has_settings);
    }

    public function test_is_installed(): void
    {
        $theme = $this->createTheme(['installed_at' => now()]);

        $this->assertTrue($theme->isInstalled());
    }

    public function test_is_not_installed(): void
    {
        $theme = $this->createTheme(['installed_at' => null]);

        $this->assertFalse($theme->isInstalled());
    }

    public function test_has_update_available(): void
    {
        $theme = $this->createTheme([
            'version' => '1.0.0',
            'available_version' => '2.0.0',
        ]);

        $this->assertTrue($theme->hasUpdateAvailable());
    }

    public function test_installed_scope(): void
    {
        $this->createTheme(['slug' => 'installed', 'installed_at' => now()]);
        $this->createTheme(['slug' => 'not-installed', 'installed_at' => null]);

        $installed = Theme::installed()->get();

        $this->assertEquals(1, $installed->count());
    }
}
