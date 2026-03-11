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

use App\Services\Plugin\PluginPermissionService;

if (! function_exists('plugin_permission')) {
    /**
     * プラグイン権限サービスのインスタンスを取得
     *
     *
     * @example
     * // 権限チェック
     * plugin_permission()->check('dixlase-inquiry', 'mail.send');
     *
     * // 権限サマリー取得
     * plugin_permission()->getSummary('dixlase-inquiry');
     */
    function plugin_permission(): PluginPermissionService
    {
        return app(PluginPermissionService::class);
    }
}

if (! function_exists('plugin_can')) {
    /**
     * プラグインが特定の権限を持っているかチェック
     *
     * @param  string  $pluginSlug  プラグインのスラッグ
     * @param  string  $permission  権限キー（ドット記法）
     *
     * @example
     * if (plugin_can('dixlase-inquiry', 'mail.send')) {
     *     // メール送信処理
     * }
     */
    function plugin_can(string $pluginSlug, string $permission): bool
    {
        return app(PluginPermissionService::class)->check($pluginSlug, $permission);
    }
}

if (! function_exists('plugin_enforce')) {
    /**
     * プラグインの権限をチェックし、違反時は例外をスロー
     *
     * @param  string  $pluginSlug  プラグインのスラッグ
     * @param  string  $permission  権限キー（ドット記法）
     * @param  string  $action  実行しようとしたアクション（ログ用）
     *
     * @throws \App\Exceptions\PluginPermissionException
     *
     * @example
     * plugin_enforce('dixlase-inquiry', 'mail.send', 'Sending inquiry notification');
     */
    function plugin_enforce(string $pluginSlug, string $permission, string $action = ''): void
    {
        app(PluginPermissionService::class)->enforce($pluginSlug, $permission, $action);
    }
}
