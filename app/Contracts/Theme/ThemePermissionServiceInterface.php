<?php

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
     * リスクレベルと理由を計算
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
