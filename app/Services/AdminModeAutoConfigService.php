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

namespace App\Services;

use App\Helpers\ConfigHelper;
use Illuminate\Support\Facades\Log;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * かんたんモードへの切り替え時に、Hidden項目の自動設定値を適用するサービス
 *
 * 各メニューの自動設定メソッドは個別に呼び出すことも、
 * applyAll() で一括適用することもできます。
 */
class AdminModeAutoConfigService
{
    /**
     * 全てのHidden項目の自動設定値を一括適用
     *
     * @return array<string, bool> メニューキーごとの適用結果
     */
    public function applyAll(): array
    {
        $results = [];

        $methods = [
            'settings.security.session' => 'applySessionDefaults',
        ];

        foreach ($methods as $menuKey => $method) {
            try {
                $this->{$method}();
                $results[$menuKey] = true;

                Log::channel('admin_activity')->info("かんたんモード自動設定を適用: {$menuKey}", [
                    'menu_key' => $menuKey,
                ]);
            } catch (\Exception $e) {
                $results[$menuKey] = false;

                Log::channel('admin_activity')->error("かんたんモード自動設定の適用に失敗: {$menuKey}", [
                    'menu_key' => $menuKey,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * セッション設定の自動設定値を適用
     *
     * 対象: settings.security.session (Hidden)
     * - session_lifetime: 120分
     * - session_encrypt: false（デフォルト値維持）
     */
    public function applySessionDefaults(): void
    {
        ConfigHelper::setSessionLifetime(120);
        ConfigHelper::setSessionEncrypt(false);
    }
}
