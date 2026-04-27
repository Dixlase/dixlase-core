<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Contracts\Plugin;

/**
 * プラグイン権限管理サービスの契約
 *
 * plugin.json の permissions セクションを読み取り、
 * プラグインの権限チェック・サマリー取得・違反記録等を提供します。
 */
interface PluginPermissionServiceInterface
{
    /**
     * プラグインの権限をチェック
     *
     * @param  string  $pluginSlug  プラグインのスラッグ（例: dixlase-inquiry）
     * @param  string  $permission  権限キー（例: mail.send, database.own_tables）
     */
    public function check(string $pluginSlug, string $permission): bool;

    /**
     * プラグインが特定の権限を持っているか確認（エイリアス）
     */
    public function has(string $pluginSlug, string $permission): bool;

    /**
     * プラグインの全権限を取得
     */
    public function getPermissions(string $pluginSlug): ?array;

    /**
     * プラグインの _optional 権限リストを取得
     *
     * @return array<string> オプショナル権限キーのリスト
     */
    public function getOptionalPermissions(string $pluginSlug): array;

    /**
     * プラグインの _notes を取得
     *
     * @return array{ja?: string, en?: string} 権限使用理由の説明
     */
    public function getPermissionNotes(string $pluginSlug): array;

    /**
     * 権限キーがオプショナルかどうかを判定
     */
    public function isOptionalPermission(string $pluginSlug, string $permissionKey): bool;

    /**
     * プラグインが特定のコアテーブルにアクセスできるかチェック
     *
     * @param  string  $table  テーブル名
     * @param  string  $access  アクセスタイプ（read, write）
     */
    public function canAccessCoreTable(string $pluginSlug, string $table, string $access = 'read'): bool;

    /**
     * プラグインが他のプラグインのコンテンツにアクセスできるかチェック
     *
     * @param  string  $targetPlugin  アクセス先のプラグイン
     * @param  string  $access  アクセスタイプ（read, write）
     */
    public function canAccessOtherPlugin(string $pluginSlug, string $targetPlugin, string $access = 'read'): bool;

    /**
     * プラグインの権限サマリーを取得（管理画面表示用）
     */
    public function getSummary(string $pluginSlug): array;

    /**
     * プラグインの署名情報を取得
     */
    public function getSignatureInfo(string $pluginSlug): array;

    /**
     * 宣言された権限と不一致情報からリスクレベルを統一計算
     *
     * @param  array  $declaredPermissions  plugin.json の permissions
     * @param  array  $mismatches  権限の不一致リスト
     * @return array{level: string, reasons: array, score: int}
     */
    public function calculateUnifiedRiskLevel(array $declaredPermissions, array $mismatches = []): array;

    /**
     * リスクレベルと理由を計算
     *
     * @deprecated calculateUnifiedRiskLevel() を使用してください。
     *
     * @return array{level: string, reasons: array, score: int}
     */
    public function calculateRiskLevelWithReasons(array $permissions): array;

    /**
     * キャッシュをクリア
     *
     * @param  string|null  $pluginSlug  特定のプラグインのみクリアする場合
     */
    public function clearCache(?string $pluginSlug = null): void;

    /**
     * 権限違反をログに記録
     *
     * @param  string  $action  実行しようとしたアクション
     */
    public function logViolation(string $pluginSlug, string $permission, string $action = ''): void;

    /**
     * 権限チェックを行い、違反時は例外をスロー
     *
     * @throws \App\Exceptions\PluginPermissionException
     */
    public function enforce(string $pluginSlug, string $permission, string $action = ''): void;
}
