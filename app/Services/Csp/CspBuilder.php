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

        // 6. レポートURIを追加
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
     * 信頼済みドメインを適切なディレクティブに追加
     */
    protected function addTrustedDomains(array $directives, array $domains): array
    {
        if (empty($domains)) {
            return $directives;
        }

        // フォント関連ドメインはfont-srcとstyle-srcに追加
        $fontDomains = array_filter($domains, fn($d) => str_contains($d, 'fonts.') || str_contains($d, 'font'));
        
        // CDN関連ドメインはscript-srcとstyle-srcに追加
        $cdnDomains = array_filter($domains, fn($d) => str_contains($d, 'cdn.') || str_contains($d, 'cdnjs.'));

        foreach ($domains as $domain) {
            // style-src
            if (!isset($directives['style-src'])) {
                $directives['style-src'] = [];
            }
            if (!in_array($domain, $directives['style-src'])) {
                $directives['style-src'][] = $domain;
            }

            // font-src（フォント関連のみ）
            if (in_array($domain, $fontDomains)) {
                if (!isset($directives['font-src'])) {
                    $directives['font-src'] = [];
                }
                if (!in_array($domain, $directives['font-src'])) {
                    $directives['font-src'][] = $domain;
                }
            }

            // script-src（CDN関連のみ）
            if (in_array($domain, $cdnDomains)) {
                if (!isset($directives['script-src'])) {
                    $directives['script-src'] = [];
                }
                if (!in_array($domain, $directives['script-src'])) {
                    $directives['script-src'][] = $domain;
                }
            }
        }

        return $directives;
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

        return $directives;
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
     * CSPモードを取得（enforce/report-only）
     */
    public function getMode(): string
    {
        // 設定ファイルのデフォルト値
        $configMode = config('csp.mode', 'report-only');

        // データベースの設定を優先
        try {
            $dbMode = SecuritySetting::get('csp_mode');
            if (!empty($dbMode)) {
                return $dbMode;
            }
        } catch (\Exception $e) {
            // データベース未設定時は設定ファイルの値を使用
        }

        return $configMode;
    }

    /**
     * CSPヘッダー名を取得
     */
    public function getHeaderName(): string
    {
        return $this->getMode() === 'enforce'
            ? 'Content-Security-Policy'
            : 'Content-Security-Policy-Report-Only';
    }
}
