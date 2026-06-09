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

namespace App\Services\Csp;

use App\Models\SecuritySetting;

/**
 * CSP Builder
 *
 * Service for building CSP header strings.
 * Merges policies from config files, database settings, and plugins/themes
 * to generate CSP headers.
 */
class CspBuilder
{
    protected CspNonceGenerator $nonceGenerator;

    protected CspPolicyRegistry $registry;

    /**
     * Current context (admin/front)
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
     * Set context
     */
    public function setContext(string $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Build CSP header string
     */
    public function build(): string
    {
        // Do not send CSP header in safe mode.
        // Sending an empty string to browsers causes inconsistent interpretation by UA (ignore/deny all),
        // so the caller (ContentSecurityPolicy middleware) detects the empty string
        // and does not add the header itself.
        // Additional security headers such as X-Frame-Options will continue to be added.
        if (session('safe_mode_csp')) {
            return '';
        }

        $directives = $this->collectAllDirectives();
        $directives = $this->processDirectives($directives);

        return $this->formatDirectives($directives);
    }

    /**
     * Collect directives from all sources
     */
    protected function collectAllDirectives(): array
    {
        // 1. Default directives from config file
        $directives = config('csp.directives', []);

        // 1.5. In development environment, add domain for Vite dev server
        $directives = $this->addViteDevServerDirectives($directives);

        // 2. Add trusted domains
        $trustedDomains = $this->getTrustedDomains();
        $directives = $this->addTrustedDomains($directives, $trustedDomains);

        // 3. Add context-specific directives
        $contextDirectives = $this->getContextDirectives();
        $directives = $this->mergeDirectives($directives, $contextDirectives);

        // 4. Add admin panel specific directives
        if ($this->isAdminContext()) {
            $adminDirectives = config('csp.admin', []);
            $directives = $this->mergeDirectives($directives, $adminDirectives);
        }

        // 5. Additional directives from database
        $dbDirectives = $this->getDatabaseDirectives();
        $directives = $this->mergeDirectives($directives, $dbDirectives);

        // 6. Directives from plugins/themes
        $registryDirectives = $this->registry->collectDirectives();
        $directives = $this->mergeDirectives($directives, $registryDirectives);

        // 7. Exclude denied domains (highest priority)
        $directives = $this->filterDeniedDomains($directives);

        // 8. Add report URI
        $directives = $this->addReportUri($directives);

        return $directives;
    }

    /**
     * Add CSP directive if Vite dev server is running
     * Determined by existence of hot file (independent of APP_ENV)
     */
    protected function addViteDevServerDirectives(array $directives): array
    {
        $hotFile = public_path('hot');
        if (! file_exists($hotFile)) {
            return $directives;
        }

        // Prefer the URL Vite announced via the hot file. It already
        // reflects the host-side port the browser is actually loading
        // from (e.g. 41173 in dev, 42173 in brand, etc.), so the CSP
        // automatically tracks per-environment Vite port overrides
        // without needing a separate config knob. Fall back to env /
        // default only when the hot file is unreadable or malformed.
        $viteHost = trim((string) @file_get_contents($hotFile));
        if ($viteHost === '' || parse_url($viteHost, PHP_URL_HOST) === null) {
            $viteHost = env('VITE_DEV_SERVER_URL', 'https://localhost:5173');
        }

        $host = parse_url($viteHost, PHP_URL_HOST);
        $port = parse_url($viteHost, PHP_URL_PORT);
        $viteWs = 'wss://'.$host.($port !== null ? ':'.$port : '');

        $viteDirectives = [
            'script-src' => [$viteHost],
            'style-src' => [$viteHost],
            'font-src' => [$viteHost],
            'connect-src' => [$viteHost, $viteWs],
        ];

        return $this->mergeDirectives($directives, $viteDirectives);
    }

    /**
     * Determine if admin panel context
     */
    protected function isAdminContext(): bool
    {
        $request = request();
        if (! $request) {
            return false;
        }

        // If URL path starts with /admin, it's the admin panel
        return str_starts_with($request->path(), 'admin');
    }

    /**
     * Get trusted domains
     */
    protected function getTrustedDomains(): array
    {
        $configDomains = config('csp.trusted_domains', []);

        // Get additional trusted domains from database
        try {
            $dbDomains = SecuritySetting::get('csp_trusted_domains', '');
            if (! empty($dbDomains)) {
                $dbDomains = array_filter(array_map('trim', explode("\n", $dbDomains)));
                $configDomains = array_merge($configDomains, $dbDomains);
            }
        } catch (\Exception $e) {
            // Ignore if database is not configured
        }

        return array_unique($configDomains);
    }

    /**
     * Get denied domains
     *
     * These domains will not be added to CSP even if declared in plugin.json/theme.json
     * (blocked with highest priority)
     */
    protected function getDeniedDomains(): array
    {
        $deniedDomains = [];

        try {
            $dbDomains = SecuritySetting::get('csp_denied_domains', '');
            if (! empty($dbDomains)) {
                $deniedDomains = array_filter(array_map('trim', explode("\n", $dbDomains)));
            }
        } catch (\Exception $e) {
            // Ignore if database is not configured
        }

        return array_unique($deniedDomains);
    }

    /**
     * Check if domain is in deny list
     */
    protected function isDeniedDomain(string $domain): bool
    {
        $deniedDomains = $this->getDeniedDomains();

        foreach ($deniedDomains as $denied) {
            // Exact match
            if ($domain === $denied) {
                return true;
            }

            // Wildcard matching (*.example.com)
            if (str_starts_with($denied, '*.')) {
                $baseDomain = substr($denied, 2);
                if (str_ends_with($domain, $baseDomain) || $domain === $baseDomain) {
                    return true;
                }
            }

            // Extract domain part from URL and check
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
     * Exclude denied domains from directive
     */
    protected function filterDeniedDomains(array $directives): array
    {
        foreach ($directives as $directive => &$values) {
            $values = array_filter($values, function ($value) {
                // Do not exclude special values ('self', 'none', 'nonce', etc.)
                if (str_starts_with($value, "'") && str_ends_with($value, "'")) {
                    return true;
                }
                // Do not exclude schemes like data:, blob:, etc.
                if (preg_match('/^[a-z]+:$/', $value)) {
                    return true;
                }

                // Allow if not in denied domains
                return ! $this->isDeniedDomain($value);
            });
            $values = array_values($values); // Reindex
        }

        return $directives;
    }

    /**
     * Add trusted domains to appropriate directives
     */
    protected function addTrustedDomains(array $directives, array $domains): array
    {
        if (empty($domains)) {
            return $directives;
        }

        // Get domain_detection_keywords and multi_purpose_keywords
        $detectionKeywords = config('csp.domain_detection_keywords', []);
        $multiPurposeKeywords = config('csp.multi_purpose_keywords', []);

        foreach ($domains as $domain) {
            $addedToDirectives = [];

            // Check multi-purpose keywords (priority)
            foreach ($multiPurposeKeywords as $keyword => $targetDirectives) {
                if (stripos($domain, $keyword) !== false) {
                    foreach ($targetDirectives as $directive) {
                        if (! in_array($directive, $addedToDirectives)) {
                            $this->addDomainToDirective($directives, $directive, $domain);
                            $addedToDirectives[] = $directive;
                        }
                    }
                }
            }

            // Check regular detection keywords
            foreach ($detectionKeywords as $directive => $keywords) {
                foreach ($keywords as $keyword) {
                    if (stripos($domain, $keyword) !== false) {
                        if (! in_array($directive, $addedToDirectives)) {
                            $this->addDomainToDirective($directives, $directive, $domain);
                            $addedToDirectives[] = $directive;
                        }
                        break; // Don't add to the same directive multiple times
                    }
                }
            }

            // If no keywords match, add to style-src by default
            if (empty($addedToDirectives)) {
                $this->addDomainToDirective($directives, 'style-src', $domain);
            }
        }

        return $directives;
    }

    /**
     * Add a concrete origin to a directive while honouring CSP's "'none' is
     * exclusive" rule.
     *
     * Per the CSP spec, `'none'` must be the only source expression for a
     * given directive; otherwise browsers warn "'none' must be the only
     * source expression" and treat the directive as if 'none' were absent.
     * mergeDirectives() applies this exclusion when consolidating
     * provider-supplied directives — apply the same rule here on the
     * trusted-domains path so adding e.g. challenges.cloudflare.com to a
     * `frame-src 'none'` baseline produces `frame-src https://...` rather
     * than `frame-src 'none' https://...`.
     */
    protected function addDomainToDirective(array &$directives, string $directive, string $domain): void
    {
        if (! isset($directives[$directive])) {
            $directives[$directive] = [];
        }

        // Drop any pre-existing 'none' so the concrete origin doesn't
        // co-exist with it (which the spec forbids).
        $directives[$directive] = array_values(array_filter(
            $directives[$directive],
            fn ($value) => $value !== "'none'"
        ));

        if (! in_array($domain, $directives[$directive], true)) {
            $directives[$directive][] = $domain;
        }
    }

    /**
     * Get context-specific directives
     */
    protected function getContextDirectives(): array
    {
        $key = $this->context === 'admin' ? 'admin_directives' : 'front_directives';

        return config("csp.{$key}", []);
    }

    /**
     * Get directives from database
     */
    protected function getDatabaseDirectives(): array
    {
        $directives = [];

        try {
            // Custom directives (stored in JSON format)
            $customDirectives = SecuritySetting::get('csp_custom_directives', '');
            if (! empty($customDirectives)) {
                $decoded = json_decode($customDirectives, true);
                if (is_array($decoded)) {
                    $directives = $decoded;
                }
            }
        } catch (\Exception $e) {
            // Ignore if database is not configured
        }

        return $directives;
    }

    /**
     * Merge directives
     */
    protected function mergeDirectives(array $base, array $additional): array
    {
        foreach ($additional as $directive => $values) {
            if (! isset($base[$directive])) {
                $base[$directive] = [];
            }

            // If 'none' is included, use only 'none'
            $valuesArray = (array) $values;
            if (in_array("'none'", $valuesArray, true)) {
                $base[$directive] = ["'none'"];

                continue;
            }

            // If 'none' exists, overwrite with new values
            if (in_array("'none'", $base[$directive], true)) {
                $base[$directive] = $valuesArray;

                continue;
            }

            foreach ($valuesArray as $value) {
                if (! in_array($value, $base[$directive], true)) {
                    $base[$directive][] = $value;
                }
            }
        }

        return $base;
    }

    /**
     * Add report URI
     */
    protected function addReportUri(array $directives): array
    {
        $reportUri = config('csp.report_uri', '/csp-report');

        if (! empty($reportUri)) {
            $directives['report-uri'] = [$reportUri];

            // Also add report-to directive (for newer browsers)
            // report-to specifies a group name, so a separate Report-To header is required
            // Use only report-uri here
        }

        return $directives;
    }

    /**
     * Process directives (nonce replacement, etc.)
     */
    protected function processDirectives(array $directives): array
    {
        $nonce = $this->nonceGenerator->getNonceDirective();

        foreach ($directives as $directive => &$values) {
            $values = array_map(function ($value) use ($nonce) {
                // Replace 'nonce' placeholder with actual nonce value
                if ($value === "'nonce'") {
                    return $nonce;
                }

                return $value;
            }, $values);
        }

        // Adjust directives based on CSP mode settings
        $directives = $this->applyModeSettings($directives);

        return $directives;
    }

    /**
     * Adjust directives based on CSP mode settings
     */
    protected function applyModeSettings(array $directives): array
    {
        $mode = $this->getCspMode();

        // Load modes settings directly from config/csp/base.php
        $baseConfig = require config_path('csp/base.php');
        $modeConfig = $baseConfig['modes'][$mode] ?? [];

        if (! isset($directives['script-src'])) {
            return $directives;
        }

        // Development mode
        if ($mode === 'development') {
            // Remove 'strict-dynamic' (conflicts with 'unsafe-inline')
            $directives['script-src'] = array_filter($directives['script-src'], function ($value) {
                return $value !== "'strict-dynamic'";
            });

            // Add 'unsafe-inline' only when allow_unsafe_inline is true
            $allowUnsafeInline = $modeConfig['allow_unsafe_inline'] ?? false;
            if ($allowUnsafeInline && ! in_array("'unsafe-inline'", $directives['script-src'])) {
                $directives['script-src'][] = "'unsafe-inline'";
            } elseif (! $allowUnsafeInline) {
                // Remove 'unsafe-inline' when allow_unsafe_inline is false
                $directives['script-src'] = array_filter($directives['script-src'], function ($value) {
                    return $value !== "'unsafe-inline'";
                });
            }

            $directives['script-src'] = array_values($directives['script-src']);
        } else {
            // Standard and strict modes

            // Remove 'unsafe-eval' when allow_eval is false
            $allowEval = $modeConfig['allow_eval'] ?? true;
            if ($allowEval === false) {
                $directives['script-src'] = array_filter($directives['script-src'], function ($value) {
                    return $value !== "'unsafe-eval'";
                });
            }

            // Remove 'unsafe-inline' when allow_inline_scripts is false
            $allowInlineScripts = $modeConfig['allow_inline_scripts'] ?? true;
            if ($allowInlineScripts === false) {
                $directives['script-src'] = array_filter($directives['script-src'], function ($value) {
                    return $value !== "'unsafe-inline'";
                });
            }

            // Remove 'strict-dynamic' when strict_dynamic is false
            $strictDynamic = $modeConfig['strict_dynamic'] ?? false;

            // Force disable strict_dynamic when using Vite dev server
            // (strict-dynamic is incompatible with Vite HMR)
            if (function_exists('is_vite_dev_server') && is_vite_dev_server()) {
                $strictDynamic = false;
            }

            if ($strictDynamic === false) {
                $directives['script-src'] = array_filter($directives['script-src'], function ($value) {
                    return $value !== "'strict-dynamic'";
                });
            }

            $directives['script-src'] = array_values($directives['script-src']);
        }

        // Control script-src-attr
        // Base value is 'none' (blocks inline handlers like onclick)
        // Only in development mode, relax to 'unsafe-inline' for compatibility with existing themes/plugins
        // In standard mode and beyond, maintain 'none' regardless of block_script_attr settings,
        // preventing inline handlers from being allowed due to misconfiguration
        $blockScriptAttr = $modeConfig['block_script_attr'] ?? true;
        if ($mode === 'development' && $blockScriptAttr === false) {
            $directives['script-src-attr'] = ["'unsafe-inline'"];
        } else {
            $directives['script-src-attr'] = ["'none'"];
        }

        // Control style-src
        if (isset($directives['style-src'])) {
            $allowInlineStyles = $modeConfig['allow_inline_styles'] ?? true;

            if ($mode === 'development') {
                // Development mode: maintain unsafe-inline
                if (! in_array("'unsafe-inline'", $directives['style-src'])) {
                    $directives['style-src'][] = "'unsafe-inline'";
                }
            } elseif ($mode === 'standard') {
                // Standard mode: follow allow_inline_styles
                if ($allowInlineStyles === false) {
                    $directives['style-src'] = array_filter($directives['style-src'], function ($value) {
                        return $value !== "'unsafe-inline'";
                    });
                    // Add nonce (if not already present)
                    if (! in_array("'nonce'", $directives['style-src'])) {
                        $directives['style-src'][] = "'nonce'";
                    }
                }
            } elseif ($mode === 'strict') {
                // Strict mode: follow allow_inline_styles
                if ($allowInlineStyles === true) {
                    // Add unsafe-inline for Alpine.js
                    if (! in_array("'unsafe-inline'", $directives['style-src'])) {
                        $directives['style-src'][] = "'unsafe-inline'";
                    }
                } else {
                    // External CSS only (remove unsafe-inline and nonce)
                    $directives['style-src'] = array_filter($directives['style-src'], function ($value) {
                        return $value !== "'unsafe-inline'" && $value !== "'nonce'";
                    });
                }
            }

            $directives['style-src'] = array_values($directives['style-src']);
        }

        return $directives;
    }

    /**
     * Get CSP mode (development/standard/strict)
     */
    protected function getCspMode(): string
    {
        // Prioritize admin_mode for admin panel context
        if ($this->isAdminContext()) {
            $adminMode = $this->getAdminCspMode();
            if ($adminMode !== null) {
                return $adminMode;
            }
        }

        // Prioritize database settings
        try {
            $dbMode = SecuritySetting::get('csp_mode');
            if (! empty($dbMode)) {
                // Convert numeric string to string mode name
                $modeMap = [
                    '0' => 'development',
                    '1' => 'standard',
                    '2' => 'strict',
                    0 => 'development',
                    1 => 'standard',
                    2 => 'strict',
                ];

                // Convert if numeric, keep as-is if string
                if (isset($modeMap[$dbMode])) {
                    return $modeMap[$dbMode];
                }

                return $dbMode;
            }
        } catch (\Exception $e) {
            // Use config file value when database settings not set
        }

        // Config file default value (loaded directly from config/csp/base.php)
        $baseConfig = require config_path('csp/base.php');

        return $baseConfig['mode'] ?? 'development';
    }

    /**
     * Get CSP mode specific to admin panel
     */
    protected function getAdminCspMode(): ?string
    {
        // admin_mode from config file
        $configAdminMode = config('csp.admin_mode');

        // Prioritize database settings
        try {
            $dbAdminMode = SecuritySetting::get('csp_admin_mode');
            if (! empty($dbAdminMode)) {
                // Convert numeric string to string mode name
                $modeMap = [
                    '0' => 'development',
                    '1' => 'standard',
                    '2' => 'strict',
                    0 => 'development',
                    1 => 'standard',
                    2 => 'strict',
                ];

                // Convert if numeric, keep as-is if string
                if (isset($modeMap[$dbAdminMode])) {
                    return $modeMap[$dbAdminMode];
                }

                return $dbAdminMode;
            }
        } catch (\Exception $e) {
            // Use config file value when database settings not set
        }

        return $configAdminMode;
    }

    /**
     * Format directives to CSP header string
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
     * Check if CSP is enabled
     */
    public function isEnabled(): bool
    {
        // Config file default value
        $configEnabled = config('csp.enabled', true);

        // Prioritize database settings
        try {
            $dbEnabled = SecuritySetting::get('csp_enabled');
            if ($dbEnabled !== null) {
                return filter_var($dbEnabled, FILTER_VALIDATE_BOOLEAN);
            }
        } catch (\Exception $e) {
            // Use config file value when database settings not set
        }

        return $configEnabled;
    }

    /**
     * Get CSP header name
     */
    public function getHeaderName(): string
    {
        $mode = $this->getCspMode();
        $modeConfig = config("csp.modes.{$mode}", []);

        // Get header name from mode settings
        if (isset($modeConfig['header'])) {
            return $modeConfig['header'];
        }

        // Default is Content-Security-Policy
        return 'Content-Security-Policy';
    }
}
