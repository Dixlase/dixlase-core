<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
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

namespace Tests\Feature\Console;

use App\Models\Theme;
use App\Services\Site\SiteContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * `dls:theme:list` must read the themes table. It used to read
 * theme_settings, whose key/value rows have no name or directory, so the
 * command failed as soon as any theme setting existed.
 */
class ThemeListCommandTest extends TestCase
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
            'version' => '1.2.3',
            'installed_at' => now(),
        ], $overrides));
    }

    public function test_lists_installed_themes_with_version_and_status_when_settings_exist(): void
    {
        $active = $this->createTheme();
        $this->createTheme([
            'name' => 'Other Theme',
            'package_name' => 'dixlase/other-theme',
            'directory' => 'OtherTheme',
            'slug' => 'other-theme',
            'namespace' => 'Themes\\OtherTheme',
            'version' => '0.4.0',
        ]);

        DB::table('theme_settings')->insert([
            'site_id' => app(SiteContext::class)->currentSiteId(),
            'key' => 'enabled_theme_id',
            'value' => (string) $active->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('dls:theme:list')
            ->expectsTable(['ID', 'Name', 'Directory', 'Version', 'Status'], [
                [$this->themeId('other-theme'), 'Other Theme', 'OtherTheme', '0.4.0', __('admin/command/theme-list.disabled')],
                [$active->id, 'Test Theme', 'TestTheme', '1.2.3', __('admin/command/theme-list.enabled')],
            ])
            ->assertExitCode(0);
    }

    public function test_reports_no_themes_when_none_is_installed_even_if_settings_exist(): void
    {
        DB::table('theme_settings')->insert([
            'site_id' => app(SiteContext::class)->currentSiteId(),
            'key' => 'enabled_theme_id',
            'value' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->artisan('dls:theme:list')
            ->expectsOutput(__('admin/command/theme-list.no_themes'))
            ->assertExitCode(0);
    }

    private function themeId(string $slug): int
    {
        return (int) Theme::where('slug', $slug)->value('id');
    }
}
