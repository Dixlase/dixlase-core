<?php
/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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

namespace App\Services\Csp;

use App\Models\SecuritySetting;

/**
 * CSP Builder
 * 
 * CSPヘッダー文字列を構築するサービス。
 * 設定ファイル、データベース設定、プラグイン/テーマからのポリシーを
 * マージしてCSPヘッダーを生成する。
 */
class CspBuilder
{
    protected CspNonceGenerator $nonceGenerator;
    protected CspPolicyRegistry $registry;

    /**
     * 現在のコンテキスト（admin/front）
     */
    protected string $context = 'front';

    public function __construct(
        CspNonceGenerator $nonceGenerator,
        CspPolicyRegistry $registry
    ) {
        $this->nonceGenerator = $nonceGenerator;
        $this->registry = $registry;
    }

    /**
     * コンテキストを設定
     */
    public function setContext(string $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * CSPヘッダー文字列を構築
     */
    public function build(): string
    {
        $directives = $this->collectAllDirectives();
        $directives = $this->processDirectives($directives);
        
        return $this->formatDirectives($directives);
    }

    /**
     * すべてのソースからディレクティブを収集
     */
    protected function collectAllDirectives(): array
    {
        // 1. 設定ファイルのデフォルトディレクティブ
        $directives = config('csp.directives', []);

        // 2. 信頼済みドメインを追加
        $trustedDomains = $this->getTrustedDomains();
        $directives = $this->addTrustedDomains($directives, $trustedDomains);

        // 3. コンテキスト固有のディレクティブを追加
        $contextDirectives = $this->getContextDirectives();
        $directives = $this->mergeDirectives($directives, $contextDirectives);

        // 4. データベースからの追加ディレクティブ
        $dbDirectives = $this->getDatabaseDirectives();
        $directives = $this->mergeDirectives($directives, $dbDirectives);

        // 5. プラグイン/テーマからのディレクティブ
        $registryDirectives = $this->registry->collectDirectives();
        $directives = $this->mergeDirectives($directives, $registryDirectives);

        // 6. 拒否ドメインを除外（最優先）
        $directives = $this->filterDeniedDomains($directives);

        // 7. レポートURIを追加
        $directives = $this->addReportUri($directives);

        return $directives;
    }

    /**
     * 信頼済みドメインを取得
     */
    protected function getTrustedDomains(): array
    {
        $configDomains = config('csp.trusted_domains', []);
        
        // データベースから追加の信頼済みドメインを取得
        try {
            $dbDomains = SecuritySetting::get('csp_trusted_domains', '');
            if (!empty($dbDomains)) {
                $dbDomains = array_filter(array_map('trim', explode("\n", $dbDomains)));
                $configDomains = array_merge($configDomains, $dbDomains);
            }
        } catch (\Exception $e) {
            // データベース未設定時は無視
        }

        return array_unique($configDomains);
    }

    /**
     * 拒否ドメインを取得
     * 
     * これらのドメインはplugin.json/theme.jsonで宣言されていても
     * CSPに追加されない（最優先でブロック）
     */
    protected function getDeniedDomains(): array
    {
        $deniedDomains = [];

        try {
            $dbDomains = SecuritySetting::get('csp_denied_domains', '');
            if (!empty($dbDomains)) {
                $deniedDomains = array_filter(array_map('trim', explode("\n", $dbDomains)));
            }
        } catch (\Exception $e) {
            // データベース未設定時は無視
        }

        return array_unique($deniedDomains);
    }

    /**
     * ドメインが拒否リストに含まれているかチェック
     */
    protected function isDeniedDomain(string $domain): bool
    {
        $deniedDomains = $this->getDeniedDomains();
        
        foreach ($deniedDomains as $denied) {
            // 完全一致
            if ($domain === $denied) {
                return true;
            }
            
            // ワイルドカードマッチング（*.example.com）
            if (str_starts_with($denied, '*.')) {
                $baseDomain = substr($denied, 2);
                if (str_ends_with($domain, $baseDomain) || $domain === $baseDomain) {
                    return true;
                }
            }
            
            // URLからドメイン部分を抽出してチェック
            $parsedDomain = parse_url($domain, PHP_URL_HOST);
            if ($parsedDomain) {
                if ($parsedDomain === $denied) {
                    return true;
                }
                if (str_starts_with($denied, '*.')) {
                    $baseDomain = substr($denied, 2);
                    if (str_ends_with($parsedDomain, $baseDomain) || $parsedDomain === $baseDomain) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * ディレクティブから拒否ドメインを除外
     */
    protected function filterDeniedDomains(array $directives): array
    {
        foreach ($directives as $directive => &$values) {
            $values = array_filter($values, function ($value) {
                // 特殊値（'self', 'none', 'nonce'等）は除外しない
                if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
                    return true;
                }
                // data:, blob: 等のスキームも除外しない
                if (preg_match('/^[a-z]+:$/', $value)) {
                    return true;
                }
                // 拒否ドメインに含まれていなければ許可
                return !$this->isDeniedDomain($value);
            });
            $values = array_values($values); // インデックスを振り直し
        }

        return $directives;
    }

    /**
     * 信頼済みドメインを適切なディレクティブに追加
     */
    protected function addTrustedDomains(array $directives, array $domains): array
    {
        if (empty($domains)) {
            return $directives;
        }

        // domain_detection_keywordsとmulti_purpose_keywordsを取得
        $detectionKeywords = config('csp.domain_detection_keywords', []);
        $multiPurposeKeywords = config('csp.multi_purpose_keywords', []);

        foreach ($domains as $domain) {
            $addedToDirectives = [];
            
            // Multi-purpose keywordsをチェック（優先）
            foreach ($multiPurposeKeywords as $keyword => $targetDirectives) {
                if (stripos($domain, $keyword) !== false) {
                    foreach ($targetDirectives as $directive) {
                        if (!in_array($directive, $addedToDirectives)) {
                            $this->addDomainToDirective($directives, $directive, $domain);
                            $addedToDirectives[] = $directive;
                        }
                    }
                }
            }
            
            // 通常のdetection keywordsをチェック
            foreach ($detectionKeywords as $directive => $keywords) {
                foreach ($keywords as $keyword) {
                    if (stripos($domain, $keyword) !== false) {
                        if (!in_array($directive, $addedToDirectives)) {
                            $this->addDomainToDirective($directives, $directive, $domain);
                            $addedToDirectives[] = $directive;
                        }
                        break; // 同じディレクティブに複数回追加しない
                    }
                }
            }
            
            // どのキーワードにもマッチしない場合は、デフォルトでstyle-srcに追加
            if (empty($addedToDirectives)) {
                $this->addDomainToDirective($directives, 'style-src', $domain);
            }
        }

        return $directives;
    }
    
    /**
     * ドメインを指定されたディレクティブに追加
     */
    protected function addDomainToDirective(array &$directives, string $directive, string $domain): void
    {
        if (!isset($directives[$directive])) {
            $directives[$directive] = [];
        }
        if (!in_array($domain, $directives[$directive])) {
            $directives[$directive][] = $domain;
        }
    }

    /**
     * コンテキスト固有のディレクティブを取得
     */
    protected function getContextDirectives(): array
    {
        $key = $this->context === 'admin' ? 'admin_directives' : 'front_directives';
        return config("csp.{$key}", []);
    }

    /**
     * データベースからディレクティブを取得
     */
    protected function getDatabaseDirectives(): array
    {
        $directives = [];

        try {
            // カスタムディレクティブ（JSON形式で保存）
            $customDirectives = SecuritySetting::get('csp_custom_directives', '');
            if (!empty($customDirectives)) {
                $decoded = json_decode($customDirectives, true);
                if (is_array($decoded)) {
                    $directives = $decoded;
                }
            }
        } catch (\Exception $e) {
            // データベース未設定時は無視
        }

        return $directives;
    }

    /**
     * ディレクティブをマージ
     */
    protected function mergeDirectives(array $base, array $additional): array
    {
        foreach ($additional as $directive => $values) {
            if (!isset($base[$directive])) {
                $base[$directive] = [];
            }

            foreach ((array) $values as $value) {
                if (!in_array($value, $base[$directive], true)) {
                    $base[$directive][] = $value;
                }
            }
        }

        return $base;
    }

    /**
     * レポートURIを追加
     */
    protected function addReportUri(array $directives): array
    {
        $reportUri = config('csp.report_uri', '/csp-report');
        
        if (!empty($reportUri)) {
            $directives['report-uri'] = [$reportUri];
            
            // report-to ディレクティブも追加（新しいブラウザ向け）
            // report-toはグループ名を指定するため、別途Report-Toヘッダーが必要
            // ここではreport-uriのみ使用
        }

        return $directives;
    }

    /**
     * ディレクティブを処理（nonce置換等）
     */
    protected function processDirectives(array $directives): array
    {
        $nonce = $this->nonceGenerator->getNonceDirective();

        foreach ($directives as $directive => &$values) {
            $values = array_map(function ($value) use ($nonce) {
                // 'nonce' プレースホルダーを実際のnonce値に置換
                if ($value === "'nonce'") {
                    return $nonce;
                }
                return $value;
            }, $values);
        }

        // CSPモード設定に基づいてディレクティブを調整
        $directives = $this->applyModeSettings($directives);

        return $directives;
    }

    /**
     * CSPモード設定に基づいてディレクティブを調整
     */
    protected function applyModeSettings(array $directives): array
    {
        $mode = $this->getCspMode();
        $modeConfig = config("csp.modes.{$mode}", []);

        if (!isset($directives['script-src'])) {
            return $directives;
        }

        // 開発モードの場合
        if ($mode === 'development') {
            // 'strict-dynamic'を削除（'unsafe-inline'と競合するため）
            $directives['script-src'] = array_filter($directives['script-src'], function ($value) {
                return $value !== "'strict-dynamic'";
            });
            
            // 'unsafe-inline'を追加（まだ存在しない場合）
            if (!in_array("'unsafe-inline'", $directives['script-src'])) {
                $directives['script-src'][] = "'unsafe-inline'";
            }
            
            $directives['script-src'] = array_values($directives['script-src']);
        } else {
            // 標準・厳格モード
            
            // allow_evalがfalseの場合、'unsafe-eval'を削除
            $allowEval = $modeConfig['allow_eval'] ?? true;
            if ($allowEval === false) {
                $directives['script-src'] = array_filter($directives['script-src'], function ($value) {
                    return $value !== "'unsafe-eval'";
                });
            }

            // allow_inline_scriptsがfalseの場合、'unsafe-inline'を削除
            $allowInlineScripts = $modeConfig['allow_inline_scripts'] ?? true;
            if ($allowInlineScripts === false) {
                $directives['script-src'] = array_filter($directives['script-src'], function ($value) {
                    return $value !== "'unsafe-inline'";
                });
            }
            
            $directives['script-src'] = array_values($directives['script-src']);
        }

        return $directives;
    }

    /**
     * CSPモード（development/standard/strict）を取得
     */
    protected function getCspMode(): string
    {
        // Vite開発サーバー実行時は強制的に開発モードを使用
        // （strict-dynamicとVite開発サーバーは互換性がないため）
        if (function_exists('is_vite_dev_server') && is_vite_dev_server()) {
            return 'development';
        }
        
        // 設定ファイルのデフォルト値
        $configMode = config('csp.mode', 'development');

        // データベースの設定を優先
        try {
            $dbMode = SecuritySetting::get('csp_mode');
            if (!empty($dbMode)) {
                // 数値文字列を文字列モード名に変換
                $modeMap = [
                    '0' => 'development',
                    '1' => 'standard',
                    '2' => 'strict',
                    0 => 'development',
                    1 => 'standard',
                    2 => 'strict',
                ];
                
                // 数値の場合は変換、文字列の場合はそのまま
                if (isset($modeMap[$dbMode])) {
                    return $modeMap[$dbMode];
                }
                
                return $dbMode;
            }
        } catch (\Exception $e) {
            // データベース未設定時は設定ファイルの値を使用
        }

        return $configMode;
    }

    /**
     * ディレクティブをCSPヘッダー文字列にフォーマット
     */
    protected function formatDirectives(array $directives): string
    {
        $parts = [];

        foreach ($directives as $directive => $values) {
            if (empty($values)) {
                continue;
            }

            $valueString = implode(' ', array_unique($values));
            $parts[] = "{$directive} {$valueString}";
        }

        return implode('; ', $parts);
    }

    /**
     * CSPが有効かどうかを確認
     */
    public function isEnabled(): bool
    {
        // 設定ファイルのデフォルト値
        $configEnabled = config('csp.enabled', true);

        // データベースの設定を優先
        try {
            $dbEnabled = SecuritySetting::get('csp_enabled');
            if ($dbEnabled !== null) {
                return filter_var($dbEnabled, FILTER_VALIDATE_BOOLEAN);
            }
        } catch (\Exception $e) {
            // データベース未設定時は設定ファイルの値を使用
        }

        return $configEnabled;
    }

    /**
     * CSPヘッダー名を取得
     */
    public function getHeaderName(): string
    {
        $mode = $this->getCspMode();
        $modeConfig = config("csp.modes.{$mode}", []);
        
        // モード設定からヘッダー名を取得
        if (isset($modeConfig['header'])) {
            return $modeConfig['header'];
        }
        
        // デフォルトはContent-Security-Policy
        return 'Content-Security-Policy';
    }
}
