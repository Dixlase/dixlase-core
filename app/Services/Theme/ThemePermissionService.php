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

namespace App\Services\Theme;

use App\Contracts\Theme\ThemePermissionServiceInterface;
use App\Models\ThemeAudit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * テーマ権限管理サービス
 *
 * theme.jsonのpermissionsセクションを読み取り、
 * テーマの権限チェックを行うサービスです。
 */
class ThemePermissionService implements ThemePermissionServiceInterface
{
    /**
     * キャッシュキーのプレフィックス
     */
    protected const CACHE_PREFIX = 'theme_permissions_';

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
            'own_tables' => false,
            'core_tables_read' => [],
            'core_tables_write' => [],
        ],
        'storage' => [
            'own_directory' => false,
            'public_uploads' => false,
            'temp_files' => false,
        ],
        'settings' => [
            'read_core' => false,
            'write_own' => true,
        ],
        'assets' => [
            'custom_css' => true,
            'custom_js' => true,
            'external_resources' => false,
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
     * テーマの権限をチェック
     *
     * @param  string  $themeSlug  テーマのスラッグ（例: dixlase-one-page）
     * @param  string  $permission  権限キー（例: assets.custom_js, database.own_tables）
     */
    public function check(string $themeSlug, string $permission): bool
    {
        $permissions = $this->getPermissions($themeSlug);

        if ($permissions === null) {
            Log::warning('Theme permissions not found', ['theme' => $themeSlug]);

            return false;
        }

        return $this->resolvePermission($permissions, $permission);
    }

    /**
     * テーマが特定の権限を持っているか確認（エイリアス）
     */
    public function has(string $themeSlug, string $permission): bool
    {
        return $this->check($themeSlug, $permission);
    }

    /**
     * テーマの全権限を取得
     */
    public function getPermissions(string $themeSlug): ?array
    {
        // メモリキャッシュを確認
        if (isset($this->loadedPermissions[$themeSlug])) {
            return $this->loadedPermissions[$themeSlug];
        }

        // ファイルキャッシュを確認
        $cacheKey = self::CACHE_PREFIX.$themeSlug;
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            $this->loadedPermissions[$themeSlug] = $cached;

            return $cached;
        }

        // theme.jsonから読み込み
        $permissions = $this->loadPermissionsFromFile($themeSlug);

        if ($permissions !== null) {
            // デフォルト値とマージ
            $permissions = $this->mergeWithDefaults($permissions);

            // キャッシュに保存
            Cache::put($cacheKey, $permissions, self::CACHE_TTL);
            $this->loadedPermissions[$themeSlug] = $permissions;
        }

        return $permissions;
    }

    /**
     * theme.jsonから権限を読み込み
     */
    protected function loadPermissionsFromFile(string $themeSlug): ?array
    {
        $themeName = $this->slugToName($themeSlug);
        $themeJsonPath = base_path("themes/{$themeName}/theme.json");

        if (! File::exists($themeJsonPath)) {
            return null;
        }

        $content = File::get($themeJsonPath);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Invalid theme.json', [
                'theme' => $themeSlug,
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
     * デフォルト値とマージ
     */
    protected function mergeWithDefaults(array $permissions): array
    {
        return array_replace_recursive($this->defaultPermissions, $permissions);
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
     * テーマの権限サマリーを取得（管理画面表示用）
     */
    public function getSummary(string $themeSlug): array
    {
        $permissions = $this->getPermissions($themeSlug);
        $signatureInfo = $this->getSignatureInfo($themeSlug);

        // 監査結果を取得
        $audit = ThemeAudit::getBySlug($themeSlug);
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

        // リスクレベルと理由を常に現在のスコアリングルールで再計算
        // （監査DBのキャッシュはスコアリングルール変更後に陳腐化するため）
        $mismatches = $auditData['mismatches'] ?? [];
        $riskResult = $this->calculateUnifiedRiskLevel($permissions, $mismatches, $themeSlug);
        $riskLevel = $riskResult['level'];
        $riskReasons = $riskResult['reasons'];
        $riskScore = $riskResult['score'];

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
            if (! empty($enabled)) {
                $baseSummary['categories'][$category] = $enabled;
            }
        }

        return $baseSummary;
    }

    /**
     * テーマの署名情報を取得
     */
    public function getSignatureInfo(string $themeSlug): array
    {
        $themeName = $this->slugToName($themeSlug);
        $themePath = base_path("themes/{$themeName}");
        $themeJsonPath = $themePath.'/theme.json';
        $signaturePath = $themePath.'/signature.sig';

        $result = [
            'status' => 'unsigned', // unsigned, valid, invalid
            'type' => null,         // official, verified, partner
            'signed_by' => null,
            'signed_at' => null,
            'key_id' => null,
        ];

        // theme.json から signing 情報を読み取る
        if (File::exists($themeJsonPath)) {
            $content = File::get($themeJsonPath);
            $data = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE && isset($data['signing'])) {
                $signing = $data['signing'];
                $result['key_id'] = $signing['key_id'] ?? null;

                // signature.sig ファイルの存在確認
                if (File::exists($signaturePath)) {
                    // TODO: 実際の署名検証ロジックを実装
                    // 現時点では署名ファイルが存在すれば valid とする（仮実装）
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
     * @param  array  $declaredPermissions  theme.json の permissions
     * @param  array  $mismatches  権限の不一致リスト（comparePermissions() の結果）
     * @return array{level: string, reasons: array, score: int}
     */
    public function calculateUnifiedRiskLevel(array $declaredPermissions, array $mismatches = [], ?string $themeSlug = null): array
    {
        // 宣言ベースのスコアリング
        $result = $this->calculateRiskLevelWithReasons($declaredPermissions, $themeSlug);
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
    public function calculateRiskLevelWithReasons(array $permissions, ?string $themeSlug = null): array
    {
        $score = 0;
        $reasons = [];

        // 高リスク権限（スコア2以上）
        if ($permissions['storage']['public_uploads'] ?? false) {
            $score += 2;
            $reasons[] = ['key' => 'storage.public_uploads', 'severity' => 'high', 'score' => 2];
        }
        if ($permissions['assets']['external_resources'] ?? false) {
            $trustedResult = $themeSlug !== null ? $this->checkExternalDomainsTrust($themeSlug) : ['all_trusted' => false, 'domains' => []];
            if ($trustedResult['all_trusted']) {
                $reasons[] = ['key' => 'assets.external_resources_trusted', 'severity' => 'info', 'score' => 0, 'details' => $trustedResult['domains']];
            } else {
                $score += 3;
                $reasons[] = ['key' => 'assets.external_resources', 'severity' => 'high', 'score' => 3];
            }
        }

        // 中リスク権限（スコア1）
        if (! empty($permissions['database']['core_tables_write'] ?? [])) {
            $score += 1;
            $reasons[] = ['key' => 'database.core_tables_write', 'severity' => 'medium', 'score' => 1];
        }
        if ($permissions['system']['register_commands'] ?? false) {
            $score += 1;
            $reasons[] = ['key' => 'system.register_commands', 'severity' => 'medium', 'score' => 1];
        }
        if ($permissions['system']['register_blade_directives'] ?? false) {
            $score += 1;
            $reasons[] = ['key' => 'system.register_blade_directives', 'severity' => 'medium', 'score' => 1];
        }
        if ($permissions['system']['modify_routes'] ?? false) {
            $score += 1;
            $reasons[] = ['key' => 'system.modify_routes', 'severity' => 'medium', 'score' => 1];
        }

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
     * テーマの外部ドメインがコアの信頼リストに全て含まれるか判定
     *
     * CSPディレクティブ設定（style-src, font-src, script-src等）に含まれる
     * ドメインと照合し、全ての外部ドメインが信頼済みであればtrueを返す。
     */
    /**
     * テーマの外部ドメインが全て信頼リストに含まれるかチェックし、ドメイン一覧も返す
     *
     * @return array{all_trusted: bool, domains: list<string>}
     */
    protected function checkExternalDomainsTrust(string $themeSlug): array
    {
        $themeName = $this->slugToName($themeSlug);
        $themeJsonPath = base_path("themes/{$themeName}/theme.json");

        if (! File::exists($themeJsonPath)) {
            return ['all_trusted' => false, 'domains' => []];
        }

        $data = json_decode(File::get($themeJsonPath), true);
        $cspConfig = $data['csp'] ?? [];
        $externalDomains = $cspConfig['external_domains'] ?? [];

        if (empty($externalDomains)) {
            return ['all_trusted' => false, 'domains' => []];
        }

        // テーマが宣言している全外部ドメインを抽出
        $themeDomains = [];
        foreach ($externalDomains as $domains) {
            if (is_array($domains)) {
                foreach ($domains as $domain) {
                    $host = parse_url($domain, PHP_URL_HOST) ?? $domain;
                    $themeDomains[] = $host;
                }
            }
        }

        if (empty($themeDomains)) {
            return ['all_trusted' => false, 'domains' => []];
        }

        $themeDomains = array_values(array_unique($themeDomains));

        // コアCSPディレクティブから信頼ドメインのホスト名を収集
        $trustedHosts = [];
        $directives = config('csp.directives', []);
        foreach ($directives as $values) {
            if (! is_array($values)) {
                continue;
            }
            foreach ($values as $value) {
                $host = parse_url($value, PHP_URL_HOST);
                if ($host) {
                    $trustedHosts[$host] = true;
                }
            }
        }

        // config/csp/domains.php の trusted_domains も収集
        $configDomains = config('csp.domains.trusted_domains', []);
        foreach ($configDomains as $domain) {
            $host = parse_url($domain, PHP_URL_HOST) ?? $domain;
            $trustedHosts[$host] = true;
        }

        // 全ての外部ドメインが信頼リストに含まれるかチェック
        foreach ($themeDomains as $host) {
            if (! isset($trustedHosts[$host])) {
                return ['all_trusted' => false, 'domains' => $themeDomains];
            }
        }

        return ['all_trusted' => true, 'domains' => $themeDomains];
    }

    /**
     * キャッシュをクリア
     *
     * @param  string|null  $themeSlug  特定のテーマのみクリアする場合
     */
    public function clearCache(?string $themeSlug = null): void
    {
        if ($themeSlug !== null) {
            Cache::forget(self::CACHE_PREFIX.$themeSlug);
            unset($this->loadedPermissions[$themeSlug]);
        } else {
            // 全テーマのキャッシュをクリア
            $this->loadedPermissions = [];
        }
    }

    /**
     * スラッグをテーマ名に変換
     *
     * @param  string  $slug  dixlase-one-page
     * @return string DixlaseOnePage
     */
    protected function slugToName(string $slug): string
    {
        return Str::studly(str_replace('-', '_', $slug));
    }

    /**
     * テーマ名をスラッグに変換
     *
     * @param  string  $name  DixlaseOnePage
     * @return string dixlase-one-page
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
    public function logViolation(string $themeSlug, string $permission, string $action = ''): void
    {
        Log::warning('Theme permission violation', [
            'theme' => $themeSlug,
            'permission' => $permission,
            'action' => $action,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
