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

use App\Models\BaseSetting;
use App\Models\SecuritySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 設定モデル（BaseSetting, SecuritySetting）の Unit テスト
 *
 * setValue/getValue の基本動作、存在しないキーのデフォルト値、上書き動作を検証
 */
class SettingModelTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // BaseSetting
    // =========================================================================

    public function test_base_setting_set_and_get_value(): void
    {
        BaseSetting::setValue('site_name', 'Test Site');

        $this->assertEquals('Test Site', BaseSetting::getValue('site_name'));
    }

    public function test_base_setting_get_returns_default_for_missing_key(): void
    {
        $this->assertEquals('default', BaseSetting::getValue('nonexistent_key', 'default'));
    }

    public function test_base_setting_overwrite_existing_value(): void
    {
        BaseSetting::setValue('site_name', 'Original');
        BaseSetting::setValue('site_name', 'Updated');

        $this->assertEquals('Updated', BaseSetting::getValue('site_name'));
    }

    public function test_base_setting_stores_in_database(): void
    {
        BaseSetting::setValue('test_key', 'test_value');

        $this->assertDatabaseHas('base_settings', [
            'name' => 'test_key',
            'value' => 'test_value',
        ]);
    }

    public function test_base_setting_boolean_value(): void
    {
        BaseSetting::setValue('feature_enabled', '1');

        $this->assertTrue((bool) BaseSetting::getValue('feature_enabled'));
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

        $this->assertDatabaseHas('security_settings', [
            'name' => 'test_security_key',
            'value' => 'secure_value',
        ]);
    }

    // =========================================================================
    // 設定の分離
    // =========================================================================

    public function test_base_and_security_settings_are_independent(): void
    {
        BaseSetting::setValue('shared_key', 'base_value');
        SecuritySetting::setValue('shared_key', 'security_value');

        $this->assertEquals('base_value', BaseSetting::getValue('shared_key'));
        $this->assertEquals('security_value', SecuritySetting::getValue('shared_key'));
    }
}
