<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Services\Plugin;

use App\Contracts\Plugin\PluginPermissionServiceInterface;
use App\Contracts\Plugin\SignatureVerifierInterface;
use App\Models\PluginAudit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * プラグイン権限管理サービス
 *
 * plugin.jsonのpermissionsセクションを読み取り、
 * プラグインの権限チェックを行うサービスです。
 */
class PluginPermissionService implements PluginPermissionServiceInterface
{
    /**
     * キャッシュキーのプレフィックス
     */
    protected const CACHE_PREFIX = 'plugin_permissions_';

    /**
     * キャッシュの有効期限（秒）
     */
    protected const CACHE_TTL = 3600;

    /**
     * 読み込み済みの権限データ
     */
    protected array $loadedPermissions = [];

    /**
     * デフォルトの権限設定
     */
    protected array $defaultPermissions = [
        'database' => [
            'own_tables' => true,
            'core_tables_read' => [],
            'core_tables_write' => [],
        ],
        'storage' => [
            'own_directory' => true,
            'public_uploads' => false,
            'temp_files' => false,
        ],
        'settings' => [
            'read_core' => false,
            'write_own' => true,
        ],
        'members' => [
            'read' => false,
            'write' => false,
            'create' => false,
            'delete' => false,
        ],
        'mail' => [
            'send' => false,
            'bulk_send' => false,
        ],
        'content' => [
            'read_other_plugins' => [],
            'write_other_plugins' => [],
        ],
        'system' => [
            'register_shortcodes' => false,
            'register_middleware' => false,
            'register_commands' => false,
            'register_blade_directives' => false,
            'modify_routes' => false,
        ],
    ];

    /**
     * プラグインの権限をチェック
     *
     * @param  string  $pluginSlug  プラグインのスラッグ（例: dixlase-inquiry）
     * @param  string  $permission  権限キー（例: mail.send, database.own_tables）
     */
    public function check(string $pluginSlug, string $permission): bool
    {
        $permissions = $this->getPermissions($pluginSlug);

        if ($permissions === null) {
            Log::warning('Plugin permissions not found', ['plugin' => $pluginSlug]);

            return false;
        }

        return $this->resolvePermission($permissions, $permission);
    }

    /**
     * プラグインが特定の権限を持っているか確認（エイリアス）
     */
    public function has(string $pluginSlug, string $permission): bool
    {
        return $this->check($pluginSlug, $permission);
    }

    /**
     * プラグインの全権限を取得
     */
    public function getPermissions(string $pluginSlug): ?array
    {
        // メモリキャッシュを確認
        if (isset($this->loadedPermissions[$pluginSlug])) {
            return $this->loadedPermissions[$pluginSlug];
        }

        // ファイルキャッシュを確認
        $cacheKey = self::CACHE_PREFIX.$pluginSlug;
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            $this->loadedPermissions[$pluginSlug] = $cached;

            return $cached;
        }

        // plugin.jsonから読み込み
        $permissions = $this->loadPermissionsFromFile($pluginSlug);

        if ($permissions !== null) {
            // デフォルト値とマージ
            $permissions = $this->mergeWithDefaults($permissions);

            // キャッシュに保存
            Cache::put($cacheKey, $permissions, self::CACHE_TTL);
            $this->loadedPermissions[$pluginSlug] = $permissions;
        }

        return $permissions;
    }

    /**
     * plugin.jsonから権限を読み込み
     */
    protected function loadPermissionsFromFile(string $pluginSlug): ?array
    {
        $pluginName = $this->slugToName($pluginSlug);
        $pluginJsonPath = base_path("plugins/{$pluginName}/plugin.json");

        if (! File::exists($pluginJsonPath)) {
            return null;
        }

        $content = File::get($pluginJsonPath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Invalid plugin.json', [
                'plugin' => $pluginSlug,
                'error' => json_last_error_msg(),
            ]);

            return null;
        }

        // permissionsセクションが存在しない場合はnullを返す
        if (! isset($data['permissions']) || empty($data['permissions'])) {
            return null;
        }

        return $data['permissions'];
    }

    /**
     * プラグインの _optional 権限リストを取得
     *
     * @return array<string> オプショナル権限キーのリスト
     */
    public function getOptionalPermissions(string $pluginSlug): array
    {
        $permissions = $this->getRawPermissions($pluginSlug);

        return $permissions['_optional'] ?? [];
    }

    /**
     * プラグインの _notes を取得
     *
     * @return array{ja?: string, en?: string} 権限使用理由の説明
     */
    public function getPermissionNotes(string $pluginSlug): array
    {
        $permissions = $this->getRawPermissions($pluginSlug);

        return $permissions['_notes'] ?? [];
    }

    /**
     * 権限キーがオプショナルかどうかを判定
     */
    public function isOptionalPermission(string $pluginSlug, string $permissionKey): bool
    {
        return in_array($permissionKey, $this->getOptionalPermissions($pluginSlug), true);
    }

    /**
     * plugin.json から生の permissions を取得（_optional, _notes 含む）
     */
    protected function getRawPermissions(string $pluginSlug): array
    {
        $pluginName = $this->slugToName($pluginSlug);
        $pluginJsonPath = base_path("plugins/{$pluginName}/plugin.json");

        if (! File::exists($pluginJsonPath)) {
            return [];
        }

        $data = json_decode(File::get($pluginJsonPath), true);
        if (json_last_error() !== JSON_ERROR_NONE || ! isset($data['permissions'])) {
            return [];
        }

        return $data['permissions'];
    }

    /**
     * デフォルト値とマージ
     *
     * _optional と _notes はメタデータのため、マージ対象から除外します。
     */
    protected function mergeWithDefaults(array $permissions): array
    {
        // メタデータを退避
        $optional = $permissions['_optional'] ?? [];
        $notes = $permissions['_notes'] ?? [];

        // メタデータを除外してマージ
        $filtered = array_diff_key($permissions, ['_optional' => true, '_notes' => true]);

        // Normalize legacy core_tables format to core_tables_read/core_tables_write
        if (isset($filtered['database']['core_tables'])) {
            $coreTablesValue = $filtered['database']['core_tables'];

            if (! isset($filtered['database']['core_tables_read'])) {
                $filtered['database']['core_tables_read'] = $coreTablesValue;
            }
            if (! isset($filtered['database']['core_tables_write'])) {
                // For boolean true, assume read-only unless write is explicitly declared
                $filtered['database']['core_tables_write'] = is_array($coreTablesValue)
                    ? $coreTablesValue
                    : false;
            }
            unset($filtered['database']['core_tables']);
        }

        $merged = array_replace_recursive($this->defaultPermissions, $filtered);

        // メタデータを復元
        if (! empty($optional)) {
            $merged['_optional'] = $optional;
        }
        if (! empty($notes)) {
            $merged['_notes'] = $notes;
        }

        return $merged;
    }

    /**
     * ドット記法の権限キーを解決
     */
    protected function resolvePermission(array $permissions, string $key): bool
    {
        $parts = explode('.', $key);
        $value = $permissions;

        foreach ($parts as $part) {
            if (! isset($value[$part])) {
                return false;
            }
            $value = $value[$part];
        }

        // 配列の場合は空でないかチェック
        if (is_array($value)) {
            return ! empty($value);
        }

        return (bool) $value;
    }

    /**
     * プラグインが特定のコアテーブルにアクセスできるかチェック
     *
     * @param  string  $table  テーブル名
     * @param  string  $access  アクセスタイプ（read, write）
     */
    public function canAccessCoreTable(string $pluginSlug, string $table, string $access = 'read'): bool
    {
        $permissions = $this->getPermissions($pluginSlug);

        if ($permissions === null) {
            return false;
        }

        // 書き込みの場合は core_tables_write をチェック
        if ($access === 'write') {
            $writeTables = $permissions['database']['core_tables_write'] ?? [];

            return in_array($table, $writeTables, true);
        }

        // 読み取りの場合は core_tables_read をチェック
        $readTables = $permissions['database']['core_tables_read'] ?? [];

        return in_array($table, $readTables, true);
    }

    /**
     * プラグインが他のプラグインのコンテンツにアクセスできるかチェック
     *
     * @param  string  $targetPlugin  アクセス先のプラグイン
     * @param  string  $access  アクセスタイプ（read, write）
     */
    public function canAccessOtherPlugin(string $pluginSlug, string $targetPlugin, string $access = 'read'): bool
    {
        $permissions = $this->getPermissions($pluginSlug);

        if ($permissions === null) {
            return false;
        }

        $key = $access === 'write' ? 'write_other_plugins' : 'read_other_plugins';
        $allowedPlugins = $permissions['content'][$key] ?? [];

        // ワイルドカード対応
        if (in_array('*', $allowedPlugins)) {
            return true;
        }

        return in_array($targetPlugin, $allowedPlugins);
    }

    /**
     * プラグインの権限サマリーを取得（管理画面表示用）
     */
    public function getSummary(string $pluginSlug): array
    {
        $permissions = $this->getPermissions($pluginSlug);
        $signatureInfo = $this->getSignatureInfo($pluginSlug);

        // 監査結果を取得
        $audit = PluginAudit::getBySlug($pluginSlug);
        $auditData = $audit ? $audit->toAuditArray() : [];

        if ($permissions === null) {
            // 権限定義がない場合でも、監査結果があればそれを使用
            $riskLevel = $auditData['risk_level'] ?? 'unknown';

            return [
                'has_permissions' => false,
                'risk_level' => $riskLevel,
                'risk_reasons' => $auditData['risk_reasons'] ?? [],
                'risk_score' => 0,
                'health_score' => $auditData['health_score'] ?? null,
                'health_status' => $auditData['health_status'] ?? null,
                'categories' => [],
                'signature' => $signatureInfo,
                'audit' => $auditData,
            ];
        }

        // リスクレベルと理由を常に現在のスコアリングルールで再計算
        // （監査DBのキャッシュはスコアリングルール変更後に陳腐化するため）
        $mismatches = $auditData['mismatches'] ?? [];
        $riskResult = $this->calculateUnifiedRiskLevel($permissions, $mismatches);
        $riskLevel = $riskResult['level'];
        $riskReasons = $riskResult['reasons'];
        $riskScore = $riskResult['score'];

        $baseSummary = [
            'has_permissions' => true,
            'risk_level' => $riskLevel,
            'risk_reasons' => $riskReasons,
            'risk_score' => $riskScore,
            'health_score' => $auditData['health_score'] ?? null,
            'health_status' => $auditData['health_status'] ?? null,
            'categories' => [],
            'optional' => $this->getOptionalPermissions($pluginSlug),
            'notes' => $this->getPermissionNotes($pluginSlug),
            'signature' => $signatureInfo,
            'audit' => $auditData,
        ];

        // メタデータキーを除外してカテゴリごとの権限をまとめる
        $metadataKeys = ['_optional', '_notes'];
        foreach ($permissions as $category => $perms) {
            if (in_array($category, $metadataKeys, true)) {
                continue;
            }
            if (! is_array($perms)) {
                continue;
            }
            $enabled = [];
            foreach ($perms as $key => $value) {
                if ($this->isPermissionEnabled($value)) {
                    $enabled[] = $key;
                }
            }
            if (! empty($enabled)) {
                $baseSummary['categories'][$category] = $enabled;
            }
        }

        return $baseSummary;
    }

    /**
     * プラグインの署名情報を取得
     *
     * SignatureVerifierInterface を使用して署名検証を行います。
     * DixlaseDevKit プラグインがインストール済みの場合は Ed25519 ベースの検証、
     * 未インストールの場合はスタブ実装（メタデータ読み取りのみ）を使用します。
     */
    public function getSignatureInfo(string $pluginSlug): array
    {
        $verifier = app(SignatureVerifierInterface::class);
        $result = $verifier->verify($pluginSlug);

        return $result->toArray();
    }

    /**
     * 権限が有効かどうかを判定
     *
     * @param  mixed  $value
     */
    protected function isPermissionEnabled($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_array($value)) {
            return ! empty($value);
        }

        return (bool) $value;
    }

    /**
     * リスクレベルを計算
     *
     * @return string low, medium, high
     */
    protected function calculateRiskLevel(array $permissions): string
    {
        $result = $this->calculateRiskLevelWithReasons($permissions);

        return $result['level'];
    }

    /**
     * リスクレベル文字列からスコアを計算
     */
    protected function calculateRiskScore(string $level): int
    {
        return match ($level) {
            'low' => 0,
            'medium' => 3,
            'high' => 6,
            default => 0,
        };
    }

    /**
     * 宣言された権限と不一致情報からリスクレベルを統一計算
     *
     * 宣言ベースのスコアリングに加え、未宣言使用（undeclared_usage）の
     * 不一致ペナルティを加算して統一的なリスクレベルを返します。
     *
     * @param  array  $declaredPermissions  plugin.json の permissions
     * @param  array  $mismatches  権限の不一致リスト（comparePermissions() の結果）
     * @return array{level: string, reasons: array, score: int}
     */
    public function calculateUnifiedRiskLevel(array $declaredPermissions, array $mismatches = []): array
    {
        // 宣言ベースのスコアリング
        $result = $this->calculateRiskLevelWithReasons($declaredPermissions);
        $score = $result['score'];
        $reasons = $result['reasons'];

        // 未宣言使用の不一致ペナルティ
        $undeclaredCount = count(array_filter($mismatches, fn ($m) => ($m['type'] ?? '') === 'undeclared_usage'));
        if ($undeclaredCount > 0) {
            $penalty = $undeclaredCount * 2;
            $score += $penalty;
            $reasons[] = ['key' => 'mismatch.undeclared_usage', 'severity' => 'high', 'score' => $penalty, 'count' => $undeclaredCount];
        }

        // しきい値判定
        $level = 'low';
        if ($score >= 7) {
            $level = 'high';
        } elseif ($score >= 3) {
            $level = 'medium';
        }

        return [
            'level' => $level,
            'reasons' => $reasons,
            'score' => $score,
        ];
    }

    /**
     * リスクレベルと理由を計算
     *
     * @deprecated calculateUnifiedRiskLevel() を使用してください。
     *
     * @return array ['level' => string, 'reasons' => array, 'score' => int]
     */
    public function calculateRiskLevelWithReasons(array $permissions): array
    {
        $score = 0;
        $reasons = [];

        // 高リスク権限（減点大）
        if ($permissions['members']['write'] ?? false) {
            $score -= 3;
            $reasons[] = ['key' => 'members.write', 'severity' => 'high', 'score' => -3];
        }
        if ($permissions['members']['create'] ?? false) {
            $score -= 3;
            $reasons[] = ['key' => 'members.create', 'severity' => 'high', 'score' => -3];
        }
        if ($permissions['members']['delete'] ?? false) {
            $score -= 4;
            $reasons[] = ['key' => 'members.delete', 'severity' => 'high', 'score' => -4];
        }
        if ($permissions['mail']['bulk_send'] ?? false) {
            $score -= 3;
            $reasons[] = ['key' => 'mail.bulk_send', 'severity' => 'high', 'score' => -3];
        }
        if ($permissions['storage']['public_uploads'] ?? false) {
            if ($permissions['storage']['own_directory'] ?? false) {
                // 中リスク: 専用ディレクトリ内で公開アップロード
                $score -= 2;
                $reasons[] = ['key' => 'storage.public_uploads_own_dir', 'severity' => 'medium', 'score' => -2];
            } else {
                // 高リスク: 公開ディレクトリ直接使用
                $score -= 4;
                $reasons[] = ['key' => 'storage.public_uploads_no_own_dir', 'severity' => 'high', 'score' => -4];
            }
        }
        if (! empty($permissions['content']['write_other_plugins'] ?? [])) {
            $score -= 2;
            $reasons[] = ['key' => 'content.write_other_plugins', 'severity' => 'high', 'score' => -2];
        }

        // 中リスク権限（減点小）
        if (! empty($permissions['database']['core_tables_write'] ?? [])) {
            $score -= 1;
            $reasons[] = ['key' => 'database.core_tables_write', 'severity' => 'medium', 'score' => -1];
        }

        $level = 'low';
        if ($score <= -7) {
            $level = 'high';
        } elseif ($score <= -3) {
            $level = 'medium';
        }

        return [
            'level' => $level,
            'reasons' => $reasons,
            'score' => $score,
        ];
    }

    /**
     * キャッシュをクリア
     *
     * @param  string|null  $pluginSlug  特定のプラグインのみクリアする場合
     */
    public function clearCache(?string $pluginSlug = null): void
    {
        if ($pluginSlug !== null) {
            Cache::forget(self::CACHE_PREFIX.$pluginSlug);
            unset($this->loadedPermissions[$pluginSlug]);
        } else {
            // 全プラグインのキャッシュをクリア
            $this->loadedPermissions = [];
            // Note: 全キャッシュクリアはCache::flush()を使うか、
            // プラグイン一覧から個別にクリアする必要がある
        }
    }

    /**
     * スラッグをプラグイン名に変換
     *
     * @param  string  $slug  dixlase-inquiry
     * @return string DixlaseInquiry
     */
    protected function slugToName(string $slug): string
    {
        return Str::studly(str_replace('-', '_', $slug));
    }

    /**
     * プラグイン名をスラッグに変換
     *
     * @param  string  $name  DixlaseInquiry
     * @return string dixlase-inquiry
     */
    protected function nameToSlug(string $name): string
    {
        return Str::kebab($name);
    }

    /**
     * 権限違反をログに記録
     *
     * @param  string  $action  実行しようとしたアクション
     */
    public function logViolation(string $pluginSlug, string $permission, string $action = ''): void
    {
        Log::warning('Plugin permission violation', [
            'plugin' => $pluginSlug,
            'permission' => $permission,
            'action' => $action,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * 権限チェックを行い、違反時は例外をスロー
     *
     * @throws \App\Exceptions\PluginPermissionException
     */
    public function enforce(string $pluginSlug, string $permission, string $action = ''): void
    {
        if (! $this->check($pluginSlug, $permission)) {
            $this->logViolation($pluginSlug, $permission, $action);
            throw new \App\Exceptions\PluginPermissionException(
                "Plugin '{$pluginSlug}' does not have permission: {$permission}"
            );
        }
    }
}
