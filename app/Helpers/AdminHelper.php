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


use App\Enums\MemberRole;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\BaseSetting;
use App\Models\MemberRolePermission;


class AdminHelper
{
    /**
     * ナビゲーション設定のキャッシュ
     * Laravelのconfig()は信頼できないため、静的変数で管理
     */
    private static $navigationCache = null;

    public static function getAdminUrl()
    {
        // インストール前やデータベース接続エラーの場合はコンフィグ値を返す
        if (!file_exists(base_path('.env')) || !env('INSTALLED', false)) {
            return config('admin.admin_url');
        }

        try {
            if (Schema::hasTable('base_settings')) {
                $adminUrl = BaseSetting::getValue('admin_url', config('admin.admin_url'));
            } else {
                $adminUrl = config('admin.admin_url');
            }
            return $adminUrl;
        } catch (\Exception $e) {
            return config('admin.admin_url');
        }
    }


    public static function canAccessMenu(string $menuKey): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        if ($user->role->value === MemberRole::SUPER_ADMIN->value) {
            return true;
        }

        $permission = MemberRolePermission::where('menu_key', $menuKey)->first();
        if (!$permission) {
            return false;
        }

        return in_array($user->role->value, explode(',', $permission->access_roles))
            || in_array($user->role->value, explode(',', $permission->view_roles));
    }

    public static function canEditMenu(string $menuKey): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        if ($user->role->value === MemberRole::SUPER_ADMIN->value) {
            return true;
        }

        $permission = MemberRolePermission::where('menu_key', $menuKey)->first();
        if (!$permission) {
            return false;
        }

        return in_array($user->role->value, explode(',', $permission->access_roles));
    }

    /**
     * 管理画面のナビゲーション設定をマージ
     * 
     * @param string $name プラグイン/テーマ名（デバッグ用）
     * @param string $configPath 設定ファイルのパス
     */
    public static function mergeAdminNavigation(string $name = 'Unknown', string $configPath = null): void
    {
        \Log::info("=== {$name}: mergeAdminNavigation START ===", [
            'timestamp' => now()->toDateTimeString(),
            'config_path' => $configPath
        ]);
        
        $configFile = $configPath;
        
        if (!file_exists($configFile)) {
            \Log::info("{$name}: Config file not found", ['path' => $configFile]);
            return;
        }

        $config = require $configFile;
        
        if (!isset($config['nav']) || !is_array($config['nav'])) {
            \Log::info("{$name}: No nav config found");
            return;
        }

        // 既存のナビゲーション設定を取得
        // IMPORTANT: 静的変数でキャッシュして、Laravelのconfig()の問題を回避
        if (self::$navigationCache !== null) {
            \Log::info("{$name}: Using cached navigation", [
                'cached_settings_children' => isset(self::$navigationCache['settings']['children']) ? array_keys(self::$navigationCache['settings']['children']) : 'none'
            ]);
            $existingNav = self::$navigationCache;
        } else {
            $fromAppConfig = app()->config['admin.nav'] ?? null;
            $fromConfigHelper = config('admin.nav', []);
            
            \Log::info("{$name}: Config retrieval comparison (first time)", [
                'from_app_config_is_null' => is_null($fromAppConfig),
                'from_app_config_keys' => $fromAppConfig ? array_keys($fromAppConfig) : 'null',
                'from_app_config_settings_children' => isset($fromAppConfig['settings']['children']) ? array_keys($fromAppConfig['settings']['children']) : 'none',
                'from_config_helper_keys' => array_keys($fromConfigHelper),
                'from_config_helper_settings_children' => isset($fromConfigHelper['settings']['children']) ? array_keys($fromConfigHelper['settings']['children']) : 'none',
            ]);
            
            $existingNav = $fromAppConfig ?? $fromConfigHelper;
        }
        
        // コアの設定が正しく読み込まれているかチェック
        // ただし、既に設定が存在する場合は再読み込みしない（テーマやプラグインの変更を保持）
        if (empty($existingNav) || 
            (!isset($existingNav['settings']['children']) || 
             !isset($existingNav['settings']['children']['base']) ||
             !isset($existingNav['settings']['children']['security']))) {
            
            \Log::warning("{$name}: Core admin.nav settings missing or incomplete, checking if reload needed");
            
            // 完全に空の場合のみ再読み込み
            if (empty($existingNav)) {
                \Log::warning("{$name}: Config is empty, forcing reload from config file");
                
                // コアの設定ファイルを直接読み込み
                $coreConfigPath = config_path('admin.php');
                if (file_exists($coreConfigPath)) {
                    $coreConfig = require $coreConfigPath;
                    if (isset($coreConfig['nav'])) {
                        // コアの設定で初期化
                        $existingNav = $coreConfig['nav'];
                        config(['admin.nav' => $existingNav]);
                        \Log::info("{$name}: Core admin.nav settings reloaded", [
                            'core_settings_children' => isset($existingNav['settings']['children']) ? array_keys($existingNav['settings']['children']) : 'none'
                        ]);
                    }
                }
            } else {
                \Log::info("{$name}: Config exists but incomplete, keeping existing config to preserve theme/plugin changes");
            }
        }
        
        \Log::info("{$name}: Before merge", [
            'existing_nav_keys' => array_keys($existingNav),
            'config_nav_keys' => array_keys($config['nav']),
            'existing_settings' => isset($existingNav['settings']) ? [
                'keys' => array_keys($existingNav['settings']),
                'children' => isset($existingNav['settings']['children']) ? array_keys($existingNav['settings']['children']) : 'no_children'
            ] : 'not_exists'
        ]);
        
        // ナビゲーション設定をマージ
        foreach ($config['nav'] as $key => $value) {
            \Log::info("{$name}: Processing nav key '{$key}'", [
                'has_children' => isset($value['children']),
                'existing_key_exists' => isset($existingNav[$key]),
                'has_insert_after' => isset($value['_insert_after']),
                'has_insert_before' => isset($value['_insert_before'])
            ]);
            
            // 挿入位置の情報を保存
            $insertAfter = $value['_insert_after'] ?? null;
            $insertBefore = $value['_insert_before'] ?? null;
            
            // _insert_after や _insert_before を削除
            unset($value['_insert_after'], $value['_insert_before']);
            
            // 既存の設定がある場合は子項目をマージ
            if (isset($existingNav[$key])) {
                \Log::info("{$name}: Merging with existing key '{$key}'", [
                    'existing_structure' => $existingNav[$key],
                    'new_structure' => $value
                ]);
                
                // 既存の設定を保持しつつ、子項目をマージ
                if (isset($value['children']) && isset($existingNav[$key]['children'])) {
                    \Log::info("{$name}: Merging children for '{$key}'", [
                        'existing_children_keys' => array_keys($existingNav[$key]['children']),
                        'new_children_keys' => array_keys($value['children'])
                    ]);
                    // 再帰的にマージ（3階層目以降も対応）
                    $existingNav[$key]['children'] = self::deepMergeNavigation(
                        $existingNav[$key]['children'],
                        $value['children'],
                        $name
                    );
                    \Log::info("{$name}: After children merge for '{$key}'", [
                        'merged_children_keys' => array_keys($existingNav[$key]['children'])
                    ]);
                } elseif (isset($value['children'])) {
                    \Log::info("{$name}: Adding children to existing '{$key}' (no existing children)");
                    $existingNav[$key]['children'] = $value['children'];
                }
                
                // その他のプロパティは既存の設定を優先（上書きしない）
                foreach ($value as $prop => $propValue) {
                    if ($prop !== 'children') {
                        \Log::info("{$name}: Skipping property '{$prop}' for '{$key}' (existing setting preserved)");
                    }
                }
            } else {
                // 新しいキーの場合は挿入位置を考慮して追加
                \Log::info("{$name}: Adding new key '{$key}'", [
                    'insert_after' => $insertAfter,
                    'insert_before' => $insertBefore
                ]);
                
                if ($insertAfter || $insertBefore) {
                    // 挿入位置が指定されている場合
                    $existingNav = self::insertNavItem($existingNav, $key, $value, $insertAfter, $insertBefore, $name);
                } else {
                    // 挿入位置が指定されていない場合は最後に追加
                    $existingNav[$key] = $value;
                }
            }
        }
        
        // マージした設定を反映
        // IMPORTANT: 静的変数にキャッシュして、次回以降のマージで使用
        \Log::info("{$name}: Before setting config", [
            'existingNav_settings_children' => isset($existingNav['settings']['children']) ? array_keys($existingNav['settings']['children']) : 'none'
        ]);
        
        // 静的変数にキャッシュ
        self::$navigationCache = $existingNav;
        
        // Laravelの設定にも反映（ビューなどで使用される）
        app()->config['admin.nav'] = $existingNav;
        config(['admin.nav' => $existingNav]);
        
        // 設定後の確認
        \Log::info("{$name}: After setting config", [
            'cached_settings_children' => isset(self::$navigationCache['settings']['children']) ? array_keys(self::$navigationCache['settings']['children']) : 'none',
            'app_config_settings_children' => isset(app()->config['admin.nav']['settings']['children']) ? array_keys(app()->config['admin.nav']['settings']['children']) : 'none'
        ]);
        
        \Log::info("=== {$name}: mergeAdminNavigation END ===", [
            'final_nav_keys' => array_keys($existingNav),
            'final_nav_full' => $existingNav,
            'final_settings_children' => isset($existingNav['settings']['children']) ? array_keys($existingNav['settings']['children']) : 'none',
            'final_settings_full' => isset($existingNav['settings']) ? $existingNav['settings'] : 'none'
        ]);
        
        // 設定セクションの詳細を出力
        if (isset($existingNav['settings'])) {
            \Log::info("=== {$name}: SETTINGS SECTION DETAIL ===", [
                'settings_full' => $existingNav['settings'],
                'settings_children_count' => isset($existingNav['settings']['children']) ? count($existingNav['settings']['children']) : 0,
                'settings_children_keys' => isset($existingNav['settings']['children']) ? array_keys($existingNav['settings']['children']) : []
            ]);
        }
    }

    /**
     * ナビゲーション項目を指定位置に挿入
     * 
     * @param array $nav 既存のナビゲーション配列
     * @param string $key 挿入する項目のキー
     * @param array $value 挿入する項目の値
     * @param string|null $insertAfter この項目の後に挿入
     * @param string|null $insertBefore この項目の前に挿入
     * @param string $name デバッグ用の名前
     * @return array 挿入後のナビゲーション配列
     */
    protected static function insertNavItem(array $nav, string $key, array $value, ?string $insertAfter, ?string $insertBefore, string $name): array
    {
        $newNav = [];
        $inserted = false;
        
        foreach ($nav as $navKey => $navValue) {
            // _insert_before の処理
            if ($insertBefore && $navKey === $insertBefore && !$inserted) {
                \Log::info("{$name}: Inserting '{$key}' before '{$insertBefore}'");
                $newNav[$key] = $value;
                $inserted = true;
            }
            
            // 既存の項目を追加
            $newNav[$navKey] = $navValue;
            
            // _insert_after の処理
            if ($insertAfter && $navKey === $insertAfter && !$inserted) {
                \Log::info("{$name}: Inserting '{$key}' after '{$insertAfter}'");
                $newNav[$key] = $value;
                $inserted = true;
            }
        }
        
        // 挿入位置が見つからなかった場合は最後に追加
        if (!$inserted) {
            \Log::warning("{$name}: Insert position not found for '{$key}', adding at the end");
            $newNav[$key] = $value;
        }
        
        return $newNav;
    }

    /**
     * ナビゲーションの子項目を再帰的にマージ
     * 
     * @param array $existing 既存のナビゲーション配列
     * @param array $new 新しいナビゲーション配列
     * @param string $name デバッグ用の名前
     * @return array マージされたナビゲーション配列
     */
    protected static function deepMergeNavigation(array $existing, array $new, string $name = 'Unknown'): array
    {
        foreach ($new as $key => $value) {
            if (isset($existing[$key])) {
                // 既存の項目がある場合
                if (is_array($value) && is_array($existing[$key])) {
                    // 両方が配列の場合
                    if (isset($value['children']) && isset($existing[$key]['children'])) {
                        // 子項目がある場合は再帰的にマージ
                        \Log::info("{$name}: Deep merging '{$key}' with children");
                        $existing[$key]['children'] = self::deepMergeNavigation(
                            $existing[$key]['children'],
                            $value['children'],
                            $name
                        );
                        // children以外のプロパティは既存を保持
                    } else {
                        // 子項目がない場合は単純にマージ
                        $existing[$key] = array_merge($existing[$key], $value);
                    }
                } else {
                    // 配列でない場合は上書き
                    $existing[$key] = $value;
                }
            } else {
                // 新しい項目の場合はそのまま追加
                \Log::info("{$name}: Adding new item '{$key}'");
                $existing[$key] = $value;
            }
        }
        
        return $existing;
    }
}
