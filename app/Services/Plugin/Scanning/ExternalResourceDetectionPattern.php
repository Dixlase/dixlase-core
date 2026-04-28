<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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

namespace App\Services\Plugin\Scanning;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * 外部リソース読み込みの検出パターン（プラグイン・テーマ共通）
 *
 * <script src="http(s)://...">、<link href="http(s)://...">、
 * <img src="http(s)://...">、fetch()、XMLHttpRequest の
 * 外部URL呼び出しを検出します。
 */
class ExternalResourceDetectionPattern extends DetectionPattern
{
    public function permissionKey(): string
    {
        return 'csp.external_resources';
    }

    public function applicableTo(): string
    {
        return 'both';
    }

    /**
     * @return array<string>
     */
    public function regexPatterns(): array
    {
        return [
            // script タグの外部ソース
            '/<script[^>]+src=[\'"]https?:\/\//i',
            // link タグの外部リソース
            '/<link[^>]+href=[\'"]https?:\/\//i',
            // img タグの外部画像
            '/<img[^>]+src=[\'"]https?:\/\//i',
            // fetch() での外部URL呼び出し
            '/fetch\s*\(\s*[\'"]https?:\/\//i',
            // XMLHttpRequest の open() での外部URL呼び出し
            '/\.open\s*\(\s*[\'"][A-Z]+[\'"],\s*[\'"]https?:\/\//i',
            // ES import での外部モジュール
            '/import\s+.*from\s+[\'"]https?:\/\//i',
        ];
    }

    /**
     * コンテキスト検証: コメント行・テスト用URL・CSP信頼済みドメインを除外
     */
    public function validateMatch(string $match, string $line, string $fileContent, string $filePath): bool
    {
        // 親クラスのコメント行除外
        if (! parent::validateMatch($match, $line, $fileContent, $filePath)) {
            return false;
        }

        // HTMLコメント内は除外
        $trimmedLine = trim($line);
        if (str_starts_with($trimmedLine, '<!--')) {
            return false;
        }

        // Bladeコメント内は除外
        if (str_starts_with($trimmedLine, '{{--')) {
            return false;
        }

        // CSP信頼済みドメインは除外
        if ($this->isTrustedDomain($line)) {
            return false;
        }

        return true;
    }

    /**
     * マッチした文字列がCSP信頼済みドメインを参照しているか判定
     */
    protected function isTrustedDomain(string $match): bool
    {
        $trustedDomains = config('csp.domains.trusted_domains', []);

        // CSPディレクティブに直接定義されたドメインも収集
        $directives = config('csp.directives', []);
        foreach ($directives as $sources) {
            if (! is_array($sources)) {
                continue;
            }
            foreach ($sources as $source) {
                if (str_starts_with($source, 'https://') || str_starts_with($source, 'http://')) {
                    $trustedDomains[] = $source;
                }
            }
        }

        $trustedDomains = array_unique($trustedDomains);

        foreach ($trustedDomains as $domain) {
            if (str_contains($match, parse_url($domain, PHP_URL_HOST) ?: '')) {
                return true;
            }
        }

        return false;
    }
}
