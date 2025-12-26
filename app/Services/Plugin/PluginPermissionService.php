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

namespace App\Services\Plugin;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Models\PluginAudit;

/**
 * プラグイン権限管理サービス
 * 
 * plugin.jsonのpermissionsセクションを読み取り、
 * プラグインの権限チェックを行うサービスです。
 */
class PluginPermissionService
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
            'core_tables' => [],
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
     * @param string $pluginSlug プラグインのスラッグ（例: dixlase-inquiry）
     * @param string $permission 権限キー（例: mail.send, database.own_tables）
     * @return bool
     */
    public function check(string $pluginSlug, string $permission): bool
    {
        $permissions = $this->getPermissions($pluginSlug);
        
        if ($permissions === null) {
            Log::warning("Plugin permissions not found", ['plugin' => $pluginSlug]);
            return false;
        }

        return $this->resolvePermission($permissions, $permission);
    }

    /**
     * プラグインが特定の権限を持っているか確認（エイリアス）
     *
     * @param string $pluginSlug
     * @param string $permission
     * @return bool
     */
    public function has(string $pluginSlug, string $permission): bool
    {
        return $this->check($pluginSlug, $permission);
    }

    /**
     * プラグインの全権限を取得
     *
     * @param string $pluginSlug
     * @return array|null
     */
    public function getPermissions(string $pluginSlug): ?array
    {
        // メモリキャッシュを確認
        if (isset($this->loadedPermissions[$pluginSlug])) {
            return $this->loadedPermissions[$pluginSlug];
        }

        // ファイルキャッシュを確認
        $cacheKey = self::CACHE_PREFIX . $pluginSlug;
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
     *
     * @param string $pluginSlug
     * @return array|null
     */
    protected function loadPermissionsFromFile(string $pluginSlug): ?array
    {
        $pluginName = $this->slugToName($pluginSlug);
        $pluginJsonPath = base_path("plugins/{$pluginName}/plugin.json");

        if (!File::exists($pluginJsonPath)) {
            return null;
        }

        $content = File::get($pluginJsonPath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("Invalid plugin.json", [
                'plugin' => $pluginSlug,
                'error' => json_last_error_msg(),
            ]);
            return null;
        }

        // permissionsセクションが存在しない場合はnullを返す
        if (!isset($data['permissions']) || empty($data['permissions'])) {
            return null;
        }

        return $data['permissions'];
    }

    /**
     * デフォルト値とマージ
     *
     * @param array $permissions
     * @return array
     */
    protected function mergeWithDefaults(array $permissions): array
    {
        return array_replace_recursive($this->defaultPermissions, $permissions);
    }

    /**
     * ドット記法の権限キーを解決
     *
     * @param array $permissions
     * @param string $key
     * @return bool
     */
    protected function resolvePermission(array $permissions, string $key): bool
    {
        $parts = explode('.', $key);
        $value = $permissions;

        foreach ($parts as $part) {
            if (!isset($value[$part])) {
                return false;
            }
            $value = $value[$part];
        }

        // 配列の場合は空でないかチェック
        if (is_array($value)) {
            return !empty($value);
        }

        return (bool) $value;
    }

    /**
     * プラグインが特定のコアテーブルにアクセスできるかチェック
     *
     * @param string $pluginSlug
     * @param string $table テーブル名
     * @param string $access アクセスタイプ（read, write）
     * @return bool
     */
    public function canAccessCoreTable(string $pluginSlug, string $table, string $access = 'read'): bool
    {
        $permissions = $this->getPermissions($pluginSlug);
        
        if ($permissions === null) {
            return false;
        }

        $coreTables = $permissions['database']['core_tables'] ?? [];
        
        foreach ($coreTables as $tablePermission) {
            // "members:read" 形式をパース
            if (is_string($tablePermission)) {
                $parts = explode(':', $tablePermission);
                $tableName = $parts[0];
                $allowedAccess = $parts[1] ?? 'read';

                if ($tableName === $table) {
                    if ($access === 'read') {
                        return true;
                    }
                    if ($access === 'write' && $allowedAccess === 'write') {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * プラグインが他のプラグインのコンテンツにアクセスできるかチェック
     *
     * @param string $pluginSlug
     * @param string $targetPlugin アクセス先のプラグイン
     * @param string $access アクセスタイプ（read, write）
     * @return bool
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
     *
     * @param string $pluginSlug
     * @return array
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
                'categories' => [],
                'signature' => $signatureInfo,
                'audit' => $auditData,
            ];
        }
        
        // リスクレベルと理由を計算（監査結果があればそちらを優先）
        if (!empty($auditData['risk_level'])) {
            $riskLevel = $auditData['risk_level'];
            $riskReasons = $auditData['risk_reasons'] ?? [];
            $riskScore = $this->calculateRiskScore($riskLevel);
        } else {
            $riskResult = $this->calculateRiskLevelWithReasons($permissions);
            $riskLevel = $riskResult['level'];
            $riskReasons = $riskResult['reasons'];
            $riskScore = $riskResult['score'];
        }
        
        $baseSummary = [
            'has_permissions' => true,
            'risk_level' => $riskLevel,
            'risk_reasons' => $riskReasons,
            'risk_score' => $riskScore,
            'categories' => [],
            'signature' => $signatureInfo,
            'audit' => $auditData,
        ];

        // カテゴリごとの権限をまとめる
        foreach ($permissions as $category => $perms) {
            $enabled = [];
            foreach ($perms as $key => $value) {
                if ($this->isPermissionEnabled($value)) {
                    $enabled[] = $key;
                }
            }
            if (!empty($enabled)) {
                $baseSummary['categories'][$category] = $enabled;
            }
        }

        return $baseSummary;
    }

    /**
     * プラグインの署名情報を取得
     *
     * @param string $pluginSlug
     * @return array
     */
    public function getSignatureInfo(string $pluginSlug): array
    {
        $pluginName = $this->slugToName($pluginSlug);
        $pluginPath = base_path("plugins/{$pluginName}");
        $pluginJsonPath = $pluginPath . '/plugin.json';
        $signaturePath = $pluginPath . '/signature.sig';
        
        $result = [
            'status' => 'unsigned', // unsigned, valid, invalid
            'type' => null,         // official, verified, partner
            'signed_by' => null,
            'signed_at' => null,
            'key_id' => null,
        ];
        
        // plugin.json から signing 情報を読み取る
        if (File::exists($pluginJsonPath)) {
            $content = File::get($pluginJsonPath);
            $data = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($data['signing'])) {
                $signing = $data['signing'];
                $result['key_id'] = $signing['key_id'] ?? null;
                
                // signature.sig ファイルの存在確認
                if (File::exists($signaturePath)) {
                    // TODO: 実際の署名検証ロジックを実装
                    // 現時点では署名ファイルが存在すれば valid とする（仮実装）
                    // 将来的には SignatureVerifier で検証する
                    $result['status'] = 'pending_verification';
                    
                    // 署名ファイルの内容を読み取る
                    $sigContent = File::get($signaturePath);
                    $sigData = json_decode($sigContent, true);
                    
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $result['signed_by'] = $sigData['signed_by'] ?? null;
                        $result['signed_at'] = $sigData['signed_at'] ?? null;
                        $result['type'] = $this->determineSignatureType($sigData['key_id'] ?? $signing['key_id'] ?? null);
                    }
                }
            }
        }
        
        return $result;
    }

    /**
     * 署名タイプを判定
     *
     * @param string|null $keyId
     * @return string|null
     */
    protected function determineSignatureType(?string $keyId): ?string
    {
        if ($keyId === null) {
            return null;
        }
        
        // キーIDのプレフィックスで判定
        if (str_starts_with($keyId, 'dixlase-official')) {
            return 'official';
        }
        if (str_starts_with($keyId, 'dixlase-verified') || str_starts_with($keyId, 'marketplace')) {
            return 'verified';
        }
        if (str_starts_with($keyId, 'partner-')) {
            return 'partner';
        }
        
        return null;
    }

    /**
     * 権限が有効かどうかを判定
     *
     * @param mixed $value
     * @return bool
     */
    protected function isPermissionEnabled($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_array($value)) {
            return !empty($value);
        }
        return (bool) $value;
    }

    /**
     * リスクレベルを計算
     *
     * @param array $permissions
     * @return string low, medium, high
     */
    protected function calculateRiskLevel(array $permissions): string
    {
        $result = $this->calculateRiskLevelWithReasons($permissions);
        return $result['level'];
    }

    /**
     * リスクレベル文字列からスコアを計算
     *
     * @param string $level
     * @return int
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
     * リスクレベルと理由を計算
     *
     * @param array $permissions
     * @return array ['level' => string, 'reasons' => array, 'score' => int]
     */
    public function calculateRiskLevelWithReasons(array $permissions): array
    {
        $score = 0;
        $reasons = [];

        // 高リスク権限（スコア2以上）
        if ($permissions['members']['write'] ?? false) {
            $score += 3;
            $reasons[] = ['key' => 'members.write', 'severity' => 'high', 'score' => 3];
        }
        if ($permissions['members']['create'] ?? false) {
            $score += 3;
            $reasons[] = ['key' => 'members.create', 'severity' => 'high', 'score' => 3];
        }
        if ($permissions['members']['delete'] ?? false) {
            $score += 4;
            $reasons[] = ['key' => 'members.delete', 'severity' => 'high', 'score' => 4];
        }
        if ($permissions['mail']['bulk_send'] ?? false) {
            $score += 3;
            $reasons[] = ['key' => 'mail.bulk_send', 'severity' => 'high', 'score' => 3];
        }
        if ($permissions['storage']['public_uploads'] ?? false) {
            $score += 2;
            $reasons[] = ['key' => 'storage.public_uploads', 'severity' => 'high', 'score' => 2];
        }
        if (!empty($permissions['content']['write_other_plugins'] ?? [])) {
            $score += 2;
            $reasons[] = ['key' => 'content.write_other_plugins', 'severity' => 'high', 'score' => 2];
        }

        // 中リスク権限（スコア1）
        if ($permissions['mail']['send'] ?? false) {
            $score += 1;
            $reasons[] = ['key' => 'mail.send', 'severity' => 'medium', 'score' => 1];
        }
        if ($permissions['settings']['read_core'] ?? false) {
            $score += 1;
            $reasons[] = ['key' => 'settings.read_core', 'severity' => 'medium', 'score' => 1];
        }
        if ($permissions['system']['register_middleware'] ?? false) {
            $score += 1;
            $reasons[] = ['key' => 'system.register_middleware', 'severity' => 'medium', 'score' => 1];
        }
        if (!empty($permissions['database']['core_tables'] ?? [])) {
            $score += 1;
            $reasons[] = ['key' => 'database.core_tables', 'severity' => 'medium', 'score' => 1];
        }

        $level = 'low';
        if ($score >= 5) {
            $level = 'high';
        } elseif ($score >= 2) {
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
     * @param string|null $pluginSlug 特定のプラグインのみクリアする場合
     * @return void
     */
    public function clearCache(?string $pluginSlug = null): void
    {
        if ($pluginSlug !== null) {
            Cache::forget(self::CACHE_PREFIX . $pluginSlug);
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
     * @param string $slug dixlase-inquiry
     * @return string DixlaseInquiry
     */
    protected function slugToName(string $slug): string
    {
        return Str::studly(str_replace('-', '_', $slug));
    }

    /**
     * プラグイン名をスラッグに変換
     *
     * @param string $name DixlaseInquiry
     * @return string dixlase-inquiry
     */
    protected function nameToSlug(string $name): string
    {
        return Str::kebab($name);
    }

    /**
     * 権限違反をログに記録
     *
     * @param string $pluginSlug
     * @param string $permission
     * @param string $action 実行しようとしたアクション
     * @return void
     */
    public function logViolation(string $pluginSlug, string $permission, string $action = ''): void
    {
        Log::warning("Plugin permission violation", [
            'plugin' => $pluginSlug,
            'permission' => $permission,
            'action' => $action,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * 権限チェックを行い、違反時は例外をスロー
     *
     * @param string $pluginSlug
     * @param string $permission
     * @param string $action
     * @throws \App\Exceptions\PluginPermissionException
     * @return void
     */
    public function enforce(string $pluginSlug, string $permission, string $action = ''): void
    {
        if (!$this->check($pluginSlug, $permission)) {
            $this->logViolation($pluginSlug, $permission, $action);
            throw new \App\Exceptions\PluginPermissionException(
                "Plugin '{$pluginSlug}' does not have permission: {$permission}"
            );
        }
    }
}
