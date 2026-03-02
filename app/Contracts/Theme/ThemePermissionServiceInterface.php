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

namespace App\Contracts\Theme;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * テーマ権限管理サービスの契約
 *
 * theme.json の permissions セクションを読み取り、
 * テーマの権限チェック・サマリー取得・違反記録等を提供します。
 */
interface ThemePermissionServiceInterface
{
    /**
     * テーマの権限をチェック
     *
     * @param  string  $themeSlug  テーマのスラッグ（例: dixlase-default-theme）
     * @param  string  $permission  権限キー（例: assets.custom_js, database.own_tables）
     */
    public function check(string $themeSlug, string $permission): bool;

    /**
     * テーマが特定の権限を持っているか確認（エイリアス）
     */
    public function has(string $themeSlug, string $permission): bool;

    /**
     * テーマの全権限を取得
     */
    public function getPermissions(string $themeSlug): ?array;

    /**
     * テーマの権限サマリーを取得（管理画面表示用）
     */
    public function getSummary(string $themeSlug): array;

    /**
     * テーマの署名情報を取得
     */
    public function getSignatureInfo(string $themeSlug): array;

    /**
     * 宣言された権限と不一致情報からリスクレベルを統一計算
     *
     * @param  array  $declaredPermissions  theme.json の permissions
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
     * @param  string|null  $themeSlug  特定のテーマのみクリアする場合
     */
    public function clearCache(?string $themeSlug = null): void;

    /**
     * 権限違反をログに記録
     *
     * @param  string  $action  実行しようとしたアクション
     */
    public function logViolation(string $themeSlug, string $permission, string $action = ''): void;
}
