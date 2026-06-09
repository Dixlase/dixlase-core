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
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * External resource detection patterns (common to plugins and themes)
 *
 * <script src="http(s)://...">、<link href="http(s)://...">、
 * Detects <img src="http(s)://...">, fetch(), XMLHttpRequest
 * external URL calls
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
            // External sources in script tags
            '/<script[^>]+src=[\'"]https?:\/\//i',
            // External resources in link tags
            '/<link[^>]+href=[\'"]https?:\/\//i',
            // External images in img tags
            '/<img[^>]+src=[\'"]https?:\/\//i',
            // External URL calls with fetch()
            '/fetch\s*\(\s*[\'"]https?:\/\//i',
            // External URL calls with XMLHttpRequest open()
            '/\.open\s*\(\s*[\'"][A-Z]+[\'"],\s*[\'"]https?:\/\//i',
            // External modules with ES import
            '/import\s+.*from\s+[\'"]https?:\/\//i',
        ];
    }

    /**
     * Context validation: exclude comment lines, test URLs, and CSP trusted domains
     */
    public function validateMatch(string $match, string $line, string $fileContent, string $filePath): bool
    {
        // Exclude comment lines from parent class
        if (! parent::validateMatch($match, $line, $fileContent, $filePath)) {
            return false;
        }

        // Exclude content within HTML comments
        $trimmedLine = trim($line);
        if (str_starts_with($trimmedLine, '<!--')) {
            return false;
        }

        // Exclude content within Blade comments
        if (str_starts_with($trimmedLine, '{{--')) {
            return false;
        }

        // Exclude CSP trusted domains
        if ($this->isTrustedDomain($line)) {
            return false;
        }

        return true;
    }

    /**
     * Determine if matched string references a CSP trusted domain
     */
    protected function isTrustedDomain(string $match): bool
    {
        $trustedDomains = config('csp.domains.trusted_domains', []);

        // Also collect domains defined directly in CSP directives
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
