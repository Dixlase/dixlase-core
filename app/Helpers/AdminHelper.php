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

namespace App\Helpers;

use App\Enums\MemberRole;
use App\Models\BaseSetting;
use App\Models\Member;
use App\Services\PermissionRegistry;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 */
class AdminHelper
{
    /**
     * ナビゲーション設定のキャッシュ
     * Laravelのconfig()は信頼できないため、静的変数で管理
     */
    private static $navigationCache = null;

    /**
     * 現在ログイン中のメンバーを取得
     */
    public static function getMember(): ?Member
    {
        return Auth::guard('member')->user();
    }

    public static function getAdminUrl()
    {
        // インストール前やデータベース接続エラーの場合はコンフィグ値を返す
        if (! file_exists(base_path('.env')) || ! env('INSTALLED', false)) {
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

        if (! $user) {
            return false;
        }

        // プロフィールは全員アクセス可能（自分自身の設定）
        if (str_starts_with($menuKey, 'profile')) {
            return true;
        }

        // ダッシュボードは全員アクセス可能（閲覧のみ）
        if ($menuKey === 'dashboard') {
            return true;
        }

        if ($user->role->value === MemberRole::SUPER_ADMIN->value) {
            return true;
        }

        // PermissionRegistryを使用して実効権限をチェック
        $effective = PermissionRegistry::getEffective($menuKey);

        // 権限定義がない場合、子項目の権限をチェック
        if ($effective === null) {
            // 子項目の権限を検索（例: media -> media.index, media.upload）
            $allPermissions = PermissionRegistry::getAllCorePermissionsFlat();
            $hasChildAccess = false;

            foreach ($allPermissions as $key => $permission) {
                if (str_starts_with($key, $menuKey.'.')) {
                    if ($user->role->value >= $permission['access_roles'] ||
                        $user->role->value >= $permission['view_roles']) {
                        $hasChildAccess = true;
                        break;
                    }
                }
            }

            return $hasChildAccess;
        }

        // ユーザーの権限値が設定された最低権限値以上であればアクセス可能
        return $user->role->value >= $effective['access_roles'] ||
               $user->role->value >= $effective['view_roles'];
    }

    /**
     * メニューの閲覧権限をチェック（編集はできないが表示はできる）
     * ユーザーの権限値がview_roles以上であれば閲覧可能
     */
    public static function canViewMenu(string $menuKey): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        // プロフィールは全員閲覧可能（自分自身の設定）
        if (str_starts_with($menuKey, 'profile')) {
            return true;
        }

        // ダッシュボードは全員閲覧可能
        if ($menuKey === 'dashboard') {
            return true;
        }

        if ($user->role->value === MemberRole::SUPER_ADMIN->value) {
            return true;
        }

        return PermissionRegistry::canView($menuKey, $user->role);
    }

    /**
     * メニューの編集権限をチェック
     * ユーザーの権限値がaccess_roles以上であれば編集可能
     */
    public static function canEditMenu(string $menuKey): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        // プロフィールは全員編集可能（自分自身の設定）
        if (str_starts_with($menuKey, 'profile')) {
            return true;
        }

        // ダッシュボードは閲覧のみで編集不可
        if ($menuKey === 'dashboard') {
            return false;
        }

        if ($user->role->value === MemberRole::SUPER_ADMIN->value) {
            return true;
        }

        return PermissionRegistry::canAccess($menuKey, $user->role);
    }

    /**
     * プラグインメニューのアクセス権限をチェック
     */
    public static function canAccessPluginMenu(string $pluginSlug, string $menuKey): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->role->value === MemberRole::SUPER_ADMIN->value) {
            return true;
        }

        // PermissionRegistryを使用
        $effective = PermissionRegistry::getPluginEffective($pluginSlug, $menuKey);

        if ($effective === null) {
            // 権限設定がない場合はADMIN以上でアクセス可能
            return $user->role->value >= MemberRole::ADMIN->value;
        }

        return $user->role->value >= $effective['access_roles'] ||
               $user->role->value >= $effective['view_roles'];
    }

    /**
     * プラグインメニューの閲覧権限をチェック
     */
    public static function canViewPluginMenu(string $pluginSlug, string $menuKey): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->role->value === MemberRole::SUPER_ADMIN->value) {
            return true;
        }

        return PermissionRegistry::canViewPlugin($pluginSlug, $menuKey, $user->role);
    }

    /**
     * プラグインメニューの編集権限をチェック
     */
    public static function canEditPluginMenu(string $pluginSlug, string $menuKey): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }

        if ($user->role->value === MemberRole::SUPER_ADMIN->value) {
            return true;
        }

        return PermissionRegistry::canAccessPlugin($pluginSlug, $menuKey, $user->role);
    }

    /**
     * メニューの編集権限をチェック（コア/プラグイン統合）
     *
     * @param  string|null  $pluginSlug  プラグインスラッグ（nullの場合はコアメニュー）
     * @param  string  $menuKey  メニューキー
     * @return bool 編集権限があればtrue
     */
    public static function canEditMenuOrPlugin(?string $pluginSlug, string $menuKey): bool
    {
        if ($pluginSlug) {
            return self::canEditPluginMenu($pluginSlug, $menuKey);
        }

        return self::canEditMenu($menuKey);
    }

    /**
     * メニューの閲覧権限をチェック（コア/プラグイン統合）
     *
     * @param  string|null  $pluginSlug  プラグインスラッグ（nullの場合はコアメニュー）
     * @param  string  $menuKey  メニューキー
     * @return bool 閲覧権限があればtrue
     */
    public static function canViewMenuOrPlugin(?string $pluginSlug, string $menuKey): bool
    {
        if ($pluginSlug) {
            return self::canViewPluginMenu($pluginSlug, $menuKey);
        }

        return self::canViewMenu($menuKey);
    }

    /**
     * 管理画面のナビゲーション設定をマージ
     *
     * @param  string  $name  プラグイン/テーマ名（デバッグ用）
     * @param  string  $configPath  設定ファイルのパス
     */
    public static function mergeAdminNavigation(string $name = 'Unknown', ?string $configPath = null): void
    {
        $configFile = $configPath;

        if (! file_exists($configFile)) {
            return;
        }

        $config = require $configFile;

        if (! isset($config['nav']) || ! is_array($config['nav'])) {
            return;
        }

        // 既存のナビゲーション設定を取得
        // IMPORTANT: 静的変数でキャッシュして、Laravelのconfig()の問題を回避
        // キャッシュがある場合は必ず使用（プラグインのマージを保持）
        if (self::$navigationCache !== null) {
            $existingNav = self::$navigationCache;
        } else {
            // 初回のみconfig()から取得
            // IMPORTANT: app()->config['admin.navigation']を必ず使用（プラグインのマージを保持）
            $fromAppConfig = app()->config['admin.navigation'] ?? null;

            // app()->configがnullの場合のみconfig()から読み込む
            if ($fromAppConfig === null) {
                $fromAppConfig = config('admin.navigation', []);
            }

            $existingNav = $fromAppConfig;
        }

        // コアの設定が正しく読み込まれているかチェック
        // ただし、既に設定が存在する場合は再読み込みしない（テーマやプラグインの変更を保持）
        if (empty($existingNav) ||
            (! isset($existingNav['settings']['children']) ||
             ! isset($existingNav['settings']['children']['base']) ||
             ! isset($existingNav['settings']['children']['security']))) {
            // 完全に空の場合のみ再読み込み
            if (empty($existingNav)) {
                // コアの設定ファイルを直接読み込み
                $coreConfigPath = config_path('admin.php');
                if (file_exists($coreConfigPath)) {
                    $coreConfig = require $coreConfigPath;
                    if (isset($coreConfig['nav'])) {
                        // コアの設定で初期化
                        $existingNav = $coreConfig['nav'];
                        config(['admin.nav' => $existingNav]);
                    }
                }
            }
        }

        // ナビゲーション設定をマージ
        foreach ($config['nav'] as $key => $value) {
            // 挿入位置の情報を保存
            $insertAfter = $value['_insert_after'] ?? null;
            $insertBefore = $value['_insert_before'] ?? null;

            // _insert_after や _insert_before を削除
            unset($value['_insert_after'], $value['_insert_before']);

            // プラグインメニューの場合、plugin_slug情報を追加
            if ($name !== 'Unknown' && $name !== 'Theme') {
                $value['plugin_slug'] = $name;
                // 子項目にもplugin_slugを追加
                if (isset($value['children'])) {
                    $value['children'] = self::addPluginSlugToChildren($value['children'], $name);
                }
            }

            // 既存の設定がある場合は子項目をマージ
            if (isset($existingNav[$key])) {
                // 既存の設定を保持しつつ、子項目をマージ
                if (isset($value['children']) && isset($existingNav[$key]['children'])) {
                    // 再帰的にマージ（3階層目以降も対応）
                    $existingNav[$key]['children'] = self::deepMergeNavigation(
                        $existingNav[$key]['children'],
                        $value['children'],
                        $name
                    );
                } elseif (isset($value['children'])) {
                    $existingNav[$key]['children'] = $value['children'];
                }
                // plugin_slug情報を更新
                if (isset($value['plugin_slug'])) {
                    $existingNav[$key]['plugin_slug'] = $value['plugin_slug'];
                }
            } else {
                // 新しいキーの場合は挿入位置を考慮して追加

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
        self::$navigationCache = $existingNav;

        // Laravelの設定にも反映（ビューなどで使用される）
        app()->config['admin.nav'] = $existingNav;
        config(['admin.nav' => $existingNav]);
    }

    /**
     * 子項目にplugin_slug情報を再帰的に追加
     *
     * @param  array  $children  子項目の配列
     * @param  string  $pluginSlug  プラグインスラッグ
     * @return array plugin_slug情報が追加された子項目の配列
     */
    protected static function addPluginSlugToChildren(array $children, string $pluginSlug): array
    {
        foreach ($children as $key => $value) {
            $children[$key]['plugin_slug'] = $pluginSlug;
            if (isset($value['children']) && is_array($value['children'])) {
                $children[$key]['children'] = self::addPluginSlugToChildren($value['children'], $pluginSlug);
            }
        }

        return $children;
    }

    /**
     * ナビゲーション項目を指定位置に挿入
     *
     * @param  array  $nav  既存のナビゲーション配列
     * @param  string  $key  挿入する項目のキー
     * @param  array  $value  挿入する項目の値
     * @param  string|null  $insertAfter  この項目の後に挿入
     * @param  string|null  $insertBefore  この項目の前に挿入
     * @param  string  $name  デバッグ用の名前
     * @return array 挿入後のナビゲーション配列
     */
    protected static function insertNavItem(array $nav, string $key, array $value, ?string $insertAfter, ?string $insertBefore, string $name): array
    {
        $newNav = [];
        $inserted = false;

        foreach ($nav as $navKey => $navValue) {
            // _insert_before の処理
            if ($insertBefore && $navKey === $insertBefore && ! $inserted) {
                $newNav[$key] = $value;
                $inserted = true;
            }

            // 既存の項目を追加
            $newNav[$navKey] = $navValue;

            // _insert_after の処理
            if ($insertAfter && $navKey === $insertAfter && ! $inserted) {
                $newNav[$key] = $value;
                $inserted = true;
            }
        }

        // 挿入位置が見つからなかった場合は最後に追加
        if (! $inserted) {
            $newNav[$key] = $value;
        }

        return $newNav;
    }

    /**
     * ナビゲーションの子項目を再帰的にマージ
     *
     * @param  array  $existing  既存のナビゲーション配列
     * @param  array  $new  新しいナビゲーション配列
     * @param  string  $name  デバッグ用の名前
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
                        $existing[$key]['children'] = self::deepMergeNavigation(
                            $existing[$key]['children'],
                            $value['children'],
                            $name
                        );
                        // children以外のプロパティは既存を保持
                    } elseif (isset($value['children'])) {
                        // 新しい項目にchildrenがある場合は追加
                        $existing[$key]['children'] = $value['children'];
                    }
                    // それ以外のプロパティは既存を保持（上書きしない）
                } else {
                    // 配列でない場合は上書き
                    $existing[$key] = $value;
                }
            } else {
                // 新しい項目の場合はそのまま追加
                $existing[$key] = $value;
            }
        }

        return $existing;
    }
}
