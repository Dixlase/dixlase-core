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

namespace App\Services\Theme;

use App\Contracts\Theme\ThemePermissionServiceInterface;
use App\Models\ThemeAudit;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Theme permission management service
 *
 * Reads the permissions section from theme.json
 * Service for checking theme permissions
 */
class ThemePermissionService implements ThemePermissionServiceInterface
{
    /**
     * Cache key prefix
     */
    protected const CACHE_PREFIX = 'theme_permissions_';

    /**
     * Cache expiration time (seconds)
     */
    protected const CACHE_TTL = 3600;

    /**
     * Loaded permission data
     */
    protected array $loadedPermissions = [];

    /**
     * Default permission settings
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
     * Check theme permission
     *
     * @param  string  $themeSlug  Theme slug (e.g., dixlase-onepage)
     * @param  string  $permission  Permission key (e.g., assets.custom_js, database.own_tables)
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
     * Check if theme has a specific permission (alias)
     */
    public function has(string $themeSlug, string $permission): bool
    {
        return $this->check($themeSlug, $permission);
    }

    /**
     * Get all permissions for theme
     */
    public function getPermissions(string $themeSlug): ?array
    {
        // Check memory cache
        if (isset($this->loadedPermissions[$themeSlug])) {
            return $this->loadedPermissions[$themeSlug];
        }

        // Check file cache
        $cacheKey = self::CACHE_PREFIX.$themeSlug;
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            $this->loadedPermissions[$themeSlug] = $cached;

            return $cached;
        }

        // Load from theme.json
        $permissions = $this->loadPermissionsFromFile($themeSlug);

        if ($permissions !== null) {
            // Merge with default values
            $permissions = $this->mergeWithDefaults($permissions);

            // Save to cache
            Cache::put($cacheKey, $permissions, self::CACHE_TTL);
            $this->loadedPermissions[$themeSlug] = $permissions;
        }

        return $permissions;
    }

    /**
     * Load permissions from theme.json
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

        // Return null if permissions section does not exist
        if (! isset($data['permissions']) || empty($data['permissions'])) {
            return null;
        }

        return $data['permissions'];
    }

    /**
     * Merge with default values
     */
    protected function mergeWithDefaults(array $permissions): array
    {
        return array_replace_recursive($this->defaultPermissions, $permissions);
    }

    /**
     * Resolve permission key in dot notation
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

        // Check if array is not empty
        if (is_array($value)) {
            return ! empty($value);
        }

        return (bool) $value;
    }

    /**
     * Get theme permission summary (for admin panel display)
     */
    public function getSummary(string $themeSlug): array
    {
        $permissions = $this->getPermissions($themeSlug);
        $signatureInfo = $this->getSignatureInfo($themeSlug);

        // Get audit results
        $audit = ThemeAudit::getBySlug($themeSlug);
        $auditData = $audit ? $audit->toAuditArray() : [];

        if ($permissions === null) {
            // Use audit results if available, even when permission definition is missing
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

        // Always recalculate risk level and reason with current scoring rules
        // (because audit DB cache becomes stale after scoring rule changes)
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

        // Group permissions by category
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
     * Get theme signature information
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

        // Read signing information from theme.json
        if (File::exists($themeJsonPath)) {
            $content = File::get($themeJsonPath);
            $data = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE && isset($data['signing'])) {
                $signing = $data['signing'];
                $result['key_id'] = $signing['key_id'] ?? null;

                // Check if signature.sig file exists
                if (File::exists($signaturePath)) {
                    // TODO: Implement actual signature verification logic
                    // Currently treat as valid if signature file exists (temporary implementation)
                    $result['status'] = 'pending_verification';

                    // Read signature file contents
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
     * Determine signature type
     */
    protected function determineSignatureType(?string $keyId): ?string
    {
        if ($keyId === null) {
            return null;
        }

        // Determine by key ID prefix
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
     * Determine if permission is enabled
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
     * Calculate score from risk level string
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
     * Calculate unified risk level from declared permissions and mismatch information
     *
     * In addition to declaration-based scoring, add mismatch penalty for undeclared usage
     * to return a unified risk level
     *
     * @param  array  $declaredPermissions  permissions in theme.json
     * @param  array  $mismatches  List of permission mismatches (result of comparePermissions())
     * @return array{level: string, reasons: array, score: int}
     */
    public function calculateUnifiedRiskLevel(array $declaredPermissions, array $mismatches = [], ?string $themeSlug = null): array
    {
        // Declaration-based scoring
        $result = $this->calculateRiskLevelWithReasons($declaredPermissions, $themeSlug);
        $score = $result['score'];
        $reasons = $result['reasons'];

        // Mismatch penalty for undeclared usage
        $undeclaredCount = count(array_filter($mismatches, fn ($m) => ($m['type'] ?? '') === 'undeclared_usage'));
        if ($undeclaredCount > 0) {
            $penalty = $undeclaredCount * 2;
            $score += $penalty;
            $reasons[] = ['key' => 'mismatch.undeclared_usage', 'severity' => 'high', 'score' => $penalty, 'count' => $undeclaredCount];
        }

        // Threshold evaluation
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
     * Calculate risk level and reason
     *
     * @deprecated Use calculateUnifiedRiskLevel() instead.
     *
     * @return array ['level' => string, 'reasons' => array, 'score' => int]
     */
    public function calculateRiskLevelWithReasons(array $permissions, ?string $themeSlug = null): array
    {
        $score = 0;
        $reasons = [];

        // High-risk permissions (score 2 or higher)
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

        // Medium-risk permissions (score 1)
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
     * Determine if all external domains of the theme are included in the Core trust list
     *
     * Included in CSP directive settings (style-src, font-src, script-src, etc.)
     * Match against domains and return true if all external domains are trusted
     */
    /**
     * Check if all external domains of the theme are included in the trust list and return the domain list as well
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

        // Extract all external domains declared by the theme
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

        // Collect trusted domain hostnames from Core CSP directives
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

        // Also collect trusted_domains from config/csp/domains.php
        $configDomains = config('csp.domains.trusted_domains', []);
        foreach ($configDomains as $domain) {
            $host = parse_url($domain, PHP_URL_HOST) ?? $domain;
            $trustedHosts[$host] = true;
        }

        // Check if all external domains are included in the trust list
        foreach ($themeDomains as $host) {
            if (! isset($trustedHosts[$host])) {
                return ['all_trusted' => false, 'domains' => $themeDomains];
            }
        }

        return ['all_trusted' => true, 'domains' => $themeDomains];
    }

    /**
     * Clear cache
     *
     * @param  string|null  $themeSlug  When clearing only a specific theme
     */
    public function clearCache(?string $themeSlug = null): void
    {
        if ($themeSlug !== null) {
            Cache::forget(self::CACHE_PREFIX.$themeSlug);
            unset($this->loadedPermissions[$themeSlug]);
        } else {
            // Clear cache for all themes
            $this->loadedPermissions = [];
        }
    }

    /**
     * Convert slug to theme name
     *
     * @param  string  $slug  dixlase-onepage
     * @return string DixlaseOnePage
     */
    protected function slugToName(string $slug): string
    {
        return Str::studly(str_replace('-', '_', $slug));
    }

    /**
     * Convert theme name to slug
     *
     * @param  string  $name  DixlaseOnePage
     * @return string dixlase-onepage
     */
    protected function nameToSlug(string $name): string
    {
        return Str::kebab($name);
    }

    /**
     * Log permission violation
     *
     * @param  string  $action  Action that was attempted
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
