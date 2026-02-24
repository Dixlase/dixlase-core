<?php

namespace App\Contracts\Plugin;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
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
     * リスクレベルと理由を計算
     *
     * @deprecated PluginHealthScorer::calculate() を使用してください。
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
