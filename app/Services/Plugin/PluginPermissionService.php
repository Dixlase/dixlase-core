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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Services\Plugin;

use App\Contracts\Plugin\PluginPermissionServiceInterface;
use App\Contracts\Plugin\SignatureVerifierInterface;
use App\Models\PluginAudit;
use App\Models\SignatureWaiver;
use App\Services\Signature\SignatureWaiverService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Plugin permission management service
 *
 * Reads the permissions section of plugin.json
 * and performs plugin permission checks
 */
class PluginPermissionService implements PluginPermissionServiceInterface
{
    /**
     * Cache key prefix
     */
    protected const CACHE_PREFIX = 'plugin_permissions_';

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
     * Check plugin permission
     *
     * @param  string  $pluginSlug  Plugin slug (e.g., dixlase-inquiry)
     * @param  string  $permission  Permission key (e.g., mail.send, database.own_tables)
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
     * Check if plugin has a specific permission (alias)
     */
    public function has(string $pluginSlug, string $permission): bool
    {
        return $this->check($pluginSlug, $permission);
    }

    /**
     * Get all plugin permissions
     */
    public function getPermissions(string $pluginSlug): ?array
    {
        // Check memory cache
        if (isset($this->loadedPermissions[$pluginSlug])) {
            return $this->loadedPermissions[$pluginSlug];
        }

        // Check file cache
        $cacheKey = self::CACHE_PREFIX.$pluginSlug;
        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            $this->loadedPermissions[$pluginSlug] = $cached;

            return $cached;
        }

        // Load from plugin.json
        $permissions = $this->loadPermissionsFromFile($pluginSlug);

        if ($permissions !== null) {
            // Merge with default values
            $permissions = $this->mergeWithDefaults($permissions);

            // Save to cache
            Cache::put($cacheKey, $permissions, self::CACHE_TTL);
            $this->loadedPermissions[$pluginSlug] = $permissions;
        }

        return $permissions;
    }

    /**
     * Load permissions from plugin.json
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

        // Return null if permissions section does not exist
        if (! isset($data['permissions']) || empty($data['permissions'])) {
            return null;
        }

        return $data['permissions'];
    }

    /**
     * Get plugin _optional permission list
     *
     * @return array<string> List of optional permission keys
     */
    public function getOptionalPermissions(string $pluginSlug): array
    {
        $permissions = $this->getRawPermissions($pluginSlug);

        return $permissions['_optional'] ?? [];
    }

    /**
     * Get the _notes of the plugin
     *
     * @return array{ja?: string, en?: string} Description of permission usage reason
     */
    public function getPermissionNotes(string $pluginSlug): array
    {
        $permissions = $this->getRawPermissions($pluginSlug);

        return $permissions['_notes'] ?? [];
    }

    /**
     * Determine if a permission key is optional
     */
    public function isOptionalPermission(string $pluginSlug, string $permissionKey): bool
    {
        return in_array($permissionKey, $this->getOptionalPermissions($pluginSlug), true);
    }

    /**
     * Get raw permissions from plugin.json (including _optional, _notes)
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
     * Merge with default values
     *
     * _optional and _notes are metadata, so they are excluded from the merge target.
     */
    protected function mergeWithDefaults(array $permissions): array
    {
        // Save metadata
        $optional = $permissions['_optional'] ?? [];
        $notes = $permissions['_notes'] ?? [];

        // Merge excluding metadata
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

        // Restore metadata
        if (! empty($optional)) {
            $merged['_optional'] = $optional;
        }
        if (! empty($notes)) {
            $merged['_notes'] = $notes;
        }

        return $merged;
    }

    /**
     * Resolve dot notation permission key
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
     * Check if plugin can access a specific Core table
     *
     * @param  string  $table  Table name
     * @param  string  $access  Access type (read, write)
     */
    public function canAccessCoreTable(string $pluginSlug, string $table, string $access = 'read'): bool
    {
        $permissions = $this->getPermissions($pluginSlug);

        if ($permissions === null) {
            return false;
        }

        // Check core_tables_write for write access
        if ($access === 'write') {
            $writeTables = $permissions['database']['core_tables_write'] ?? [];

            return in_array($table, $writeTables, true);
        }

        // Check core_tables_read for read access
        $readTables = $permissions['database']['core_tables_read'] ?? [];

        return in_array($table, $readTables, true);
    }

    /**
     * Check if plugin can access content of other plugins
     *
     * @param  string  $targetPlugin  Target plugin to access
     * @param  string  $access  Access type (read, write)
     */
    public function canAccessOtherPlugin(string $pluginSlug, string $targetPlugin, string $access = 'read'): bool
    {
        $permissions = $this->getPermissions($pluginSlug);

        if ($permissions === null) {
            return false;
        }

        $key = $access === 'write' ? 'write_other_plugins' : 'read_other_plugins';
        $allowedPlugins = $permissions['content'][$key] ?? [];

        // Wildcard support
        if (in_array('*', $allowedPlugins)) {
            return true;
        }

        return in_array($targetPlugin, $allowedPlugins);
    }

    /**
     * Get plugin permission summary (for admin panel display)
     */
    public function getSummary(string $pluginSlug): array
    {
        $permissions = $this->getPermissions($pluginSlug);
        $signatureInfo = $this->getSignatureInfo($pluginSlug);

        // Get audit results
        $audit = PluginAudit::getBySlug($pluginSlug);
        $auditData = $audit ? $audit->toAuditArray() : [];

        if ($permissions === null) {
            // Use audit results even if there is no permission definition
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

        // Always recalculate risk level and reason using current scoring rules
        // (cached audit DB data becomes stale after scoring rule changes)
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

        // Exclude metadata keys and group permissions by category
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
     * Get plugin signature information
     *
     * Performs signature verification using SignatureVerifierInterface.
     * If DixlaseDevKit plugin is installed, uses Ed25519-based verification;
     * if not installed, uses stub implementation (metadata reading only).
     */
    public function getSignatureInfo(string $pluginSlug): array
    {
        $verifier = app(SignatureVerifierInterface::class);
        $result = $verifier->verify($pluginSlug);

        // Overlay the operator waiver (if any) onto the objective result. The
        // verifier status is left untouched; only the `waived` flag is added.
        $waived = app(SignatureWaiverService::class)
            ->isWaived(SignatureWaiver::SCOPE_PLUGIN, $pluginSlug);

        return $result->withWaived($waived)->toArray();
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
     * Calculate risk level
     *
     * @return string low, medium, high
     */
    protected function calculateRiskLevel(array $permissions): string
    {
        $result = $this->calculateRiskLevelWithReasons($permissions);

        return $result['level'];
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
     * Uniformly calculate risk level from declared permissions and mismatch information
     *
     * In addition to declaration-based scoring, adds
     * mismatch penalty for undeclared_usage to return a unified risk level.
     *
     * @param  array  $declaredPermissions  permissions in plugin.json
     * @param  array  $mismatches  Permission mismatch list (result of comparePermissions())
     * @return array{level: string, reasons: array, score: int}
     */
    public function calculateUnifiedRiskLevel(array $declaredPermissions, array $mismatches = []): array
    {
        // Declaration-based scoring (converts to positive value since internally uses negative scores)
        $result = $this->calculateRiskLevelWithReasons($declaredPermissions);
        $score = abs($result['score']);
        $reasons = array_map(fn ($r) => array_merge($r, ['score' => abs($r['score'])]), $result['reasons']);

        // Mismatch penalty for undeclared usage
        $undeclaredCount = count(array_filter($mismatches, fn ($m) => ($m['type'] ?? '') === 'undeclared_usage'));
        if ($undeclaredCount > 0) {
            $penalty = $undeclaredCount * 2;
            $score += $penalty;
            $reasons[] = ['key' => 'mismatch.undeclared_usage', 'severity' => 'high', 'score' => $penalty, 'count' => $undeclaredCount];
        }

        // Threshold determination
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
    public function calculateRiskLevelWithReasons(array $permissions): array
    {
        $score = 0;
        $reasons = [];

        // High-risk permissions (large penalty)
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
                // Medium risk: public upload within dedicated directory
                $score -= 2;
                $reasons[] = ['key' => 'storage.public_uploads_own_dir', 'severity' => 'medium', 'score' => -2];
            } else {
                // High risk: direct use of public directory
                $score -= 4;
                $reasons[] = ['key' => 'storage.public_uploads_no_own_dir', 'severity' => 'high', 'score' => -4];
            }
        }
        if (! empty($permissions['content']['write_other_plugins'] ?? [])) {
            // Inter-plugin cooperation is a legitimate integration pattern in Dixlase (e.g., SEO
            // writing page metadata), so no penalty is applied to the risk score.
            // However, the fact that it "writes to other plugin tables" should be visible to operators,
            // so it remains as an attention reason with severity=medium (yellow),
            // with no numeric badge (score=0).
            $reasons[] = ['key' => 'content.write_other_plugins', 'severity' => 'medium', 'score' => 0];
        }

        // Medium-risk permissions (small penalty)
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
     * Clear cache
     *
     * @param  string|null  $pluginSlug  When clearing only a specific plugin
     */
    public function clearCache(?string $pluginSlug = null): void
    {
        if ($pluginSlug !== null) {
            Cache::forget(self::CACHE_PREFIX.$pluginSlug);
            unset($this->loadedPermissions[$pluginSlug]);
        } else {
            // Clear cache for all plugins
            $this->loadedPermissions = [];
            // Note: To clear all cache, use Cache::flush() or
            // clear individually from the plugin list
        }
    }

    /**
     * Convert slug to plugin name
     *
     * @param  string  $slug  dixlase-inquiry
     * @return string DixlaseInquiry
     */
    protected function slugToName(string $slug): string
    {
        return Str::studly(str_replace('-', '_', $slug));
    }

    /**
     * Convert plugin name to slug
     *
     * @param  string  $name  DixlaseInquiry
     * @return string dixlase-inquiry
     */
    protected function nameToSlug(string $name): string
    {
        return Str::kebab($name);
    }

    /**
     * Log permission violation
     *
     * @param  string  $action  Action attempted to execute
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
     * Check permission and throw exception on violation
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
