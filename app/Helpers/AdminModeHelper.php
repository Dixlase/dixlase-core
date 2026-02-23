<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
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

namespace App\Helpers;

use App\Enums\AdminMode;
use App\Enums\MenuVisibility;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 */
class AdminModeHelper
{
    private static ?AdminMode $currentMode = null;

    private static ?array $visibilities = null;

    /**
     * 現在の管理画面モードを取得
     */
    public static function getCurrentMode(): AdminMode
    {
        if (self::$currentMode !== null) {
            return self::$currentMode;
        }

        try {
            if (! Schema::hasTable('base_settings')) {
                self::$currentMode = AdminMode::default();

                return self::$currentMode;
            }

            $value = DB::table('base_settings')
                ->where('name', 'admin_mode')
                ->value('value');

            self::$currentMode = AdminMode::fromInt($value !== null ? (int) $value : null);
        } catch (\Exception $e) {
            Log::warning('admin_mode取得に失敗: '.$e->getMessage());
            self::$currentMode = AdminMode::default();
        }

        return self::$currentMode;
    }

    /**
     * かんたんモードかどうか
     */
    public static function isSimpleMode(): bool
    {
        return self::getCurrentMode()->isSimple();
    }

    /**
     * 詳細モードかどうか
     */
    public static function isAdvancedMode(): bool
    {
        return self::getCurrentMode()->isAdvanced();
    }

    /**
     * メニュー表示設定を取得
     */
    public static function getVisibilities(): array
    {
        if (self::$visibilities !== null) {
            return self::$visibilities;
        }

        // 詳細モードではすべてFull
        if (self::isAdvancedMode()) {
            self::$visibilities = [];

            return self::$visibilities;
        }

        // デフォルト設定を取得
        $defaults = config('admin.mode.simple_defaults', []);
        $defaultValues = [];
        foreach ($defaults as $key => $vis) {
            $defaultValues[$key] = $vis instanceof MenuVisibility ? $vis->value : (int) $vis;
        }

        // 保存済み設定を取得
        try {
            if (Schema::hasTable('base_settings')) {
                $json = DB::table('base_settings')
                    ->where('name', 'admin_mode_visibilities')
                    ->value('value');

                if (! empty($json)) {
                    $saved = json_decode($json, true);
                    if (is_array($saved)) {
                        // 保存済み設定でデフォルトを上書き
                        self::$visibilities = array_merge($defaultValues, $saved);

                        return self::$visibilities;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('admin_mode_visibilities取得に失敗: '.$e->getMessage());
        }

        self::$visibilities = $defaultValues;

        return self::$visibilities;
    }

    /**
     * 指定メニューキーの表示レベルを取得
     *
     * @param  string  $menuKey  ドット記法のメニューキー (例: 'settings.security')
     */
    public static function getMenuVisibility(string $menuKey): MenuVisibility
    {
        // 詳細モードではすべてFull
        if (self::isAdvancedMode()) {
            return MenuVisibility::Full;
        }

        $visibilities = self::getVisibilities();

        if (isset($visibilities[$menuKey])) {
            return MenuVisibility::fromInt((int) $visibilities[$menuKey]);
        }

        // 親キーの設定を継承
        $parts = explode('.', $menuKey);
        while (count($parts) > 1) {
            array_pop($parts);
            $parentKey = implode('.', $parts);
            if (isset($visibilities[$parentKey])) {
                return MenuVisibility::fromInt((int) $visibilities[$parentKey]);
            }
        }

        // トップレベルキー
        if (isset($visibilities[$parts[0]])) {
            return MenuVisibility::fromInt((int) $visibilities[$parts[0]]);
        }

        return MenuVisibility::Full;
    }

    /**
     * メニューが表示可能かどうか（Hidden以外）
     */
    public static function isMenuVisible(string $menuKey): bool
    {
        return self::getMenuVisibility($menuKey) !== MenuVisibility::Hidden;
    }

    /**
     * メニューが編集可能かどうか（Full or Partial）
     */
    public static function isMenuEditable(string $menuKey): bool
    {
        $vis = self::getMenuVisibility($menuKey);

        return $vis === MenuVisibility::Full || $vis === MenuVisibility::Partial;
    }

    /**
     * メニューが読み取り専用かどうか
     */
    public static function isMenuReadOnly(string $menuKey): bool
    {
        return self::getMenuVisibility($menuKey) === MenuVisibility::ReadOnly;
    }

    /**
     * メニューが導線のみかどうか
     */
    public static function isMenuGuideOnly(string $menuKey): bool
    {
        return self::getMenuVisibility($menuKey) === MenuVisibility::GuideOnly;
    }

    /**
     * キャッシュをクリア
     */
    public static function clearCache(): void
    {
        self::$currentMode = null;
        self::$visibilities = null;
    }
}
