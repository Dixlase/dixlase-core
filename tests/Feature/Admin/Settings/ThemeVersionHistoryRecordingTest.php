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

declare(strict_types=1);

namespace Tests\Feature\Admin\Settings;

use App\Http\Controllers\Admin\Settings\AdminThemesSettingsController;
use App\Models\AuditLog;
use App\Models\Theme;
use App\Models\ThemeVersionHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Mirror of PluginVersionHistoryRecordingTest for themes — covers the
 * supply-chain version-history recording path inside
 * AdminThemesSettingsController.
 */
class ThemeVersionHistoryRecordingTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_records_history_row_with_no_old_values(): void
    {
        $theme = $this->makeTheme([
            'signing_key_id' => 'dixlase-authority-2026',
            'author_id' => 'exc-d-inc',
            'authority_key_id' => 'dixlase-authority-2026',
        ]);

        $this->invokeRecordVersionHistory(
            theme: $theme,
            oldVersion: null,
            oldSigningKeyId: null,
            oldAuthorId: null,
            installationMethod: ThemeVersionHistory::METHOD_INSTALL,
        );

        $row = ThemeVersionHistory::query()
            ->where('theme_slug', $theme->slug)
            ->firstOrFail();

        $this->assertNull($row->old_version);
        $this->assertSame($theme->version, $row->new_version);
        $this->assertFalse($row->signing_key_changed);
        $this->assertFalse($row->author_id_changed);
        $this->assertSame(ThemeVersionHistory::METHOD_INSTALL, $row->installation_method);
    }

    public function test_signing_key_change_sets_flag_and_writes_audit_event(): void
    {
        $theme = $this->makeTheme([
            'signing_key_id' => 'dixlase-authority-2027',
            'author_id' => 'exc-d-inc',
        ]);

        $this->invokeRecordVersionHistory(
            theme: $theme,
            oldVersion: '1.0.0',
            oldSigningKeyId: 'dixlase-authority-2026',
            oldAuthorId: 'exc-d-inc',
            installationMethod: ThemeVersionHistory::METHOD_UPDATE,
        );

        $row = ThemeVersionHistory::query()
            ->where('theme_slug', $theme->slug)
            ->firstOrFail();

        $this->assertTrue($row->signing_key_changed);
        $this->assertFalse($row->author_id_changed);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_THEME_SIGNING_KEY_CHANGED,
        ]);
    }

    public function test_author_id_change_sets_flag_and_writes_audit_event(): void
    {
        $theme = $this->makeTheme([
            'signing_key_id' => 'dixlase-authority-2026',
            'author_id' => 'new-owner',
        ]);

        $this->invokeRecordVersionHistory(
            theme: $theme,
            oldVersion: '1.0.0',
            oldSigningKeyId: 'dixlase-authority-2026',
            oldAuthorId: 'exc-d-inc',
            installationMethod: ThemeVersionHistory::METHOD_UPDATE,
        );

        $row = ThemeVersionHistory::query()
            ->where('theme_slug', $theme->slug)
            ->firstOrFail();

        $this->assertFalse($row->signing_key_changed);
        $this->assertTrue($row->author_id_changed);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLog::ACTION_THEME_AUTHOR_ID_CHANGED,
        ]);
    }

    public function test_first_install_with_null_old_values_does_not_falsely_trigger_change_flags(): void
    {
        $theme = $this->makeTheme([
            'signing_key_id' => 'dixlase-authority-2026',
            'author_id' => 'exc-d-inc',
        ]);

        $this->invokeRecordVersionHistory(
            theme: $theme,
            oldVersion: null,
            oldSigningKeyId: null,
            oldAuthorId: null,
            installationMethod: ThemeVersionHistory::METHOD_INSTALL,
        );

        $row = ThemeVersionHistory::query()
            ->where('theme_slug', $theme->slug)
            ->firstOrFail();

        $this->assertFalse($row->signing_key_changed);
        $this->assertFalse($row->author_id_changed);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeTheme(array $overrides = []): Theme
    {
        return Theme::create(array_merge([
            'name' => 'Test Theme',
            'package_name' => 'dixlase/test-theme',
            'directory' => 'TestTheme',
            'namespace' => 'Themes\\TestTheme',
            'slug' => 'test-theme',
            'version' => '1.1.0',
            'author' => 'Test Author',
            'installed_at' => now(),
        ], $overrides));
    }

    private function invokeRecordVersionHistory(
        Theme $theme,
        ?string $oldVersion,
        ?string $oldSigningKeyId,
        ?string $oldAuthorId,
        string $installationMethod,
    ): void {
        $controller = $this->app->make(AdminThemesSettingsController::class);
        $method = new ReflectionMethod($controller, 'recordVersionHistory');
        $method->setAccessible(true);
        $method->invoke($controller, $theme, $oldVersion, $oldSigningKeyId, $oldAuthorId, $installationMethod);
    }
}
