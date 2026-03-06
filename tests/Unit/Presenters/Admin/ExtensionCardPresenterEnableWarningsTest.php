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

namespace Tests\Unit\Presenters\Admin;

use App\Presenters\Admin\ExtensionCardPresenter;
use ReflectionMethod;
use Tests\TestCase;

class ExtensionCardPresenterEnableWarningsTest extends TestCase
{
    /**
     * private static メソッドを呼び出すヘルパー
     */
    private function callComputePluginEnableWarnings($plugin, ?array $permissionSummary): array
    {
        $method = new ReflectionMethod(ExtensionCardPresenter::class, 'computePluginEnableWarnings');

        return $method->invoke(null, $plugin, $permissionSummary);
    }

    /**
     * has_permissions が true の場合、権限未定義の警告が出ないことを確認
     */
    public function test_no_undefined_warning_when_has_permissions_is_true(): void
    {
        $summary = [
            'has_permissions' => true,
            'risk_level' => 'low',
            'signature' => ['status' => 'unsigned'],
            'audit' => ['has_mismatches' => false, 'audited_at' => '2026-03-01 00:00:00'],
        ];

        $warnings = $this->callComputePluginEnableWarnings(new \stdClass(), $summary);

        $undefinedWarning = __('admin/settings/plugins/index.permissions.install_warning_undefined');
        $this->assertNotContains($undefinedWarning, $warnings);
    }

    /**
     * has_permissions が false の場合、権限未定義の警告が出ることを確認
     */
    public function test_undefined_warning_when_has_permissions_is_false(): void
    {
        $summary = [
            'has_permissions' => false,
            'risk_level' => 'low',
            'signature' => ['status' => 'unsigned'],
            'audit' => ['has_mismatches' => false, 'audited_at' => '2026-03-01 00:00:00'],
        ];

        $warnings = $this->callComputePluginEnableWarnings(new \stdClass(), $summary);

        $undefinedWarning = __('admin/settings/plugins/index.permissions.install_warning_undefined');
        $this->assertContains($undefinedWarning, $warnings);
    }

    /**
     * has_permissions キーが存在しない場合、デフォルトで false（警告が出る）
     */
    public function test_undefined_warning_when_has_permissions_key_missing(): void
    {
        $summary = [
            'risk_level' => 'low',
            'signature' => ['status' => 'unsigned'],
            'audit' => ['has_mismatches' => false, 'audited_at' => '2026-03-01 00:00:00'],
        ];

        $warnings = $this->callComputePluginEnableWarnings(new \stdClass(), $summary);

        $undefinedWarning = __('admin/settings/plugins/index.permissions.install_warning_undefined');
        $this->assertContains($undefinedWarning, $warnings);
    }

    /**
     * categories 配列が存在しても has_permissions を正しく参照することを確認
     * （旧バグでは 'permissions' キーを参照していたため categories に依存していた）
     */
    public function test_uses_has_permissions_not_permissions_key(): void
    {
        // has_permissions = true だが、permissions キーは空 → 警告は出ないはず
        $summary = [
            'has_permissions' => true,
            'permissions' => [],  // 旧バグではこのキーを参照していた
            'risk_level' => 'low',
            'signature' => ['status' => 'unsigned'],
            'audit' => ['has_mismatches' => false, 'audited_at' => '2026-03-01 00:00:00'],
        ];

        $warnings = $this->callComputePluginEnableWarnings(new \stdClass(), $summary);

        $undefinedWarning = __('admin/settings/plugins/index.permissions.install_warning_undefined');
        $this->assertNotContains($undefinedWarning, $warnings);
    }

    /**
     * 高リスクの場合に高リスク警告が出ることを確認
     */
    public function test_high_risk_warning(): void
    {
        $summary = [
            'has_permissions' => true,
            'risk_level' => 'high',
            'signature' => ['status' => 'unsigned'],
            'audit' => ['has_mismatches' => false, 'audited_at' => '2026-03-01 00:00:00'],
        ];

        $warnings = $this->callComputePluginEnableWarnings(new \stdClass(), $summary);

        $highRiskWarning = __('admin/settings/plugins/index.permissions.enable_warning_high_risk');
        $this->assertContains($highRiskWarning, $warnings);
    }
}
