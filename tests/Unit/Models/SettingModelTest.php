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

use App\Enums\SettingScope;
use App\Models\SecuritySetting;
use App\Models\SiteSetting;
use App\Services\Site\SettingDefinition;
use App\Services\Site\SettingDefinitionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 設定モデル（SiteSetting, SecuritySetting）の Unit テスト
 *
 * setValue/getValue の基本動作、存在しないキーのデフォルト値、上書き動作を検証
 */
class SettingModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // SettingResolver は strict mode で動作するため、テスト用キーを登録
        $registry = app(SettingDefinitionRegistry::class);
        foreach ([
            'site_name', 'nonexistent_key', 'test_key', 'feature_enabled',
            'login_attempt_limit_enabled', 'login_attempt_max_attempts',
            'test_security_key', 'shared_key',
        ] as $key) {
            $registry->register(new SettingDefinition($key, SettingScope::Overridable));
        }
    }

    // =========================================================================
    // SiteSetting
    // =========================================================================

    public function test_base_setting_set_and_get_value(): void
    {
        SiteSetting::setValue('site_name', 'Test Site');

        $this->assertEquals('Test Site', SiteSetting::getValue('site_name'));
    }

    public function test_base_setting_get_returns_default_for_missing_key(): void
    {
        $this->assertEquals('default', SiteSetting::getValue('nonexistent_key', 'default'));
    }

    public function test_base_setting_overwrite_existing_value(): void
    {
        SiteSetting::setValue('site_name', 'Original');
        SiteSetting::setValue('site_name', 'Updated');

        $this->assertEquals('Updated', SiteSetting::getValue('site_name'));
    }

    public function test_base_setting_stores_in_database(): void
    {
        SiteSetting::setValue('test_key', 'test_value');

        // Overridable scope without explicit site_id writes globally via SettingResolver.
        $this->assertDatabaseHas('global_settings', [
            'name' => 'test_key',
            'value' => 'test_value',
        ]);
    }

    public function test_base_setting_boolean_value(): void
    {
        SiteSetting::setValue('feature_enabled', '1');

        $this->assertTrue((bool) SiteSetting::getValue('feature_enabled'));
    }

    // =========================================================================
    // SecuritySetting
    // =========================================================================

    public function test_security_setting_set_and_get_value(): void
    {
        SecuritySetting::setValue('login_attempt_limit_enabled', true);

        $this->assertTrue((bool) SecuritySetting::getValue('login_attempt_limit_enabled'));
    }

    public function test_security_setting_get_returns_default_for_missing_key(): void
    {
        $this->assertEquals(5, SecuritySetting::getValue('nonexistent_key', 5));
    }

    public function test_security_setting_overwrite_existing_value(): void
    {
        SecuritySetting::setValue('login_attempt_max_attempts', 3);
        SecuritySetting::setValue('login_attempt_max_attempts', 10);

        $this->assertEquals(10, SecuritySetting::getValue('login_attempt_max_attempts'));
    }

    public function test_security_setting_stores_in_database(): void
    {
        SecuritySetting::setValue('test_security_key', 'secure_value');

        // Multisite consolidation moved SecuritySetting storage to global_settings.
        $this->assertDatabaseHas('global_settings', [
            'name' => 'test_security_key',
            'value' => 'secure_value',
        ]);
    }

    // =========================================================================
    // 設定の分離
    // =========================================================================

    public function test_base_and_security_settings_share_storage_after_consolidation(): void
    {
        // After v0.1.0 multisite consolidation, SiteSetting and SecuritySetting
        // both delegate to SettingResolver and share the same backing store
        // (global_settings for Global/Overridable scopes). The historical
        // "independence" guarantee no longer holds — the last write wins.
        SiteSetting::setValue('shared_key', 'base_value');
        SecuritySetting::setValue('shared_key', 'security_value');

        $this->assertEquals('security_value', SiteSetting::getValue('shared_key'));
        $this->assertEquals('security_value', SecuritySetting::getValue('shared_key'));
    }
}
