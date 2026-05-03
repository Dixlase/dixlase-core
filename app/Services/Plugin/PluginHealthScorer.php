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

namespace App\Services\Plugin;

use App\DTO\Plugin\HealthIssue;
use App\DTO\Plugin\HealthScoreResult;
use App\Enums\ExtensionSecurityLevel;
use App\Enums\ExtensionSecurityPreset;
use App\Enums\PluginEnableAction;
use App\Enums\PluginHealthStatus;
use App\Models\PluginAudit;
use App\Services\SecuritySettingsRegistry;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Sole source of plugin health score calculation
 *
 * Uses PluginHealthStatus::getDeductionRules() as the deduction table,
 * evaluates signature verification, permission integrity, CSP compliance, dangerous API detection, and scan freshness
 * Returns a score from 0-100 and health status
 *
 * @internal Core use only. Do not reference from plugins/themes
 */
class PluginHealthScorer
{
    /**
     * Initial score
     */
    protected const BASE_SCORE = 100;

    /**
     * Days after which scan is considered expired
     */
    protected const SCAN_EXPIRY_DAYS = 30;

    public function __construct(
        protected PluginPermissionService $permissionService,
    ) {}

    /**
     * Calculate plugin health score
     */
    public function calculate(string $pluginSlug): HealthScoreResult
    {
        // Prerequisite check: NotVerified determination
        $audit = PluginAudit::getBySlug($pluginSlug);
        $permissions = $this->permissionService->getPermissions($pluginSlug);

        if ($audit === null || $audit->audited_at === null) {
            return new HealthScoreResult(
                score: 0,
                status: PluginHealthStatus::NotVerified,
                issues: [new HealthIssue(
                    type: 'not_verified_no_scan',
                    severity: 'warning',
                    description: __('services/plugin/plugin_health_scorer.audit_scan_not_executed'),
                    deduction: 0,
                )],
                hasCriticalIssue: false,
            );
        }

        $deductionRules = PluginHealthStatus::getDeductionRules();
        $issues = [];

        // If permissions section is undefined, deduct points and continue evaluation
        if ($permissions === null) {
            $issues[] = new HealthIssue(
                type: 'permission_undefined',
                severity: 'warning',
                description: __('services/plugin/plugin_health_scorer.permissions_section_undefined'),
                deduction: $deductionRules['permission_undefined'] ?? -10,
            );
        }

        // 1. Signature verification evaluation
        $issues = array_merge($issues, $this->evaluateSignature($pluginSlug, $deductionRules));

        // 2. Permission integrity evaluation (skip if permissions undefined)
        if ($permissions !== null) {
            $issues = array_merge($issues, $this->evaluatePermissions($pluginSlug, $deductionRules));
        }

        // 3. CSP compliance evaluation
        $issues = array_merge($issues, $this->evaluateCsp($pluginSlug, $deductionRules));

        // 4. Dangerous API detection evaluation
        $issues = array_merge($issues, $this->evaluateDangerousApis($pluginSlug, $deductionRules));

        // 5. Scan freshness evaluation
        $issues = array_merge($issues, $this->evaluateScanFreshness($pluginSlug, $deductionRules));

        // 6. Risky permission evaluation (public_uploads, etc.)
        if ($permissions !== null) {
            $issues = array_merge($issues, $this->evaluateRiskPermissions($permissions, $deductionRules));
        }

        // 7. Supply chain defense metadata evaluation (author_id / authority_key_id)
        $issues = array_merge($issues, $this->evaluateSupplyChainMetadata($pluginSlug, $deductionRules));

        // Calculate total score
        $totalDeduction = array_sum(array_map(fn (HealthIssue $i) => $i->deduction, $issues));
        $score = max(0, self::BASE_SCORE + $totalDeduction);

        // Determine critical issues
        $hasCriticalIssue = $this->hasCriticalIssue($issues);

        // Determine status
        $status = PluginHealthStatus::fromScore($score, $hasCriticalIssue);

        return new HealthScoreResult(
            score: $score,
            status: $status,
            issues: $issues,
            hasCriticalIssue: $hasCriticalIssue,
        );
    }

    /**
     * Evaluate signature verification
     *
     * @return array<HealthIssue>
     */
    protected function evaluateSignature(string $pluginSlug, array $deductionRules): array
    {
        $issues = [];
        $signatureInfo = $this->permissionService->getSignatureInfo($pluginSlug);

        if ($signatureInfo['status'] === 'unsigned') {
            $issues[] = new HealthIssue(
                type: 'signature_unsigned',
                severity: 'warning',
                description: __('services/plugin/plugin_health_scorer.no_signature_recommend_signing'),
                deduction: $deductionRules['signature_unsigned'] ?? -10,
            );
        } elseif ($signatureInfo['status'] === 'invalid') {
            $issues[] = new HealthIssue(
                type: 'signature_invalid',
                severity: 'critical',
                description: __('services/plugin/plugin_health_scorer.signature_invalid_tampering'),
                deduction: $deductionRules['signature_invalid'] ?? -50,
            );
        } elseif ($signatureInfo['status'] === 'pending_verification') {
            $issues[] = new HealthIssue(
                type: 'signature_pending_verification',
                severity: 'warning',
                description: __('services/plugin/plugin_health_scorer.signature_verification_incomplete_keyserver'),
                deduction: $deductionRules['signature_pending_verification'] ?? -5,
            );
        } elseif ($signatureInfo['status'] === 'unknown_key') {
            $issues[] = new HealthIssue(
                type: 'signature_unknown_key',
                severity: 'warning',
                description: __('services/plugin/plugin_health_scorer.signing_key_not_trusted'),
                deduction: $deductionRules['signature_unknown_key'] ?? -15,
            );
        } elseif ($signatureInfo['status'] === 'expired') {
            $issues[] = new HealthIssue(
                type: 'signature_expired',
                severity: 'warning',
                description: __('services/plugin/plugin_health_scorer.signing_key_revoked'),
                deduction: $deductionRules['signature_expired'] ?? -20,
            );
        } elseif ($signatureInfo['status'] === 'error') {
            $issues[] = new HealthIssue(
                type: 'signature_error',
                severity: 'warning',
                description: __('services/plugin/plugin_health_scorer.signature_verification_error'),
                deduction: $deductionRules['signature_error'] ?? -10,
            );
        }

        return $issues;
    }

    /**
     * Evaluate permission consistency
     *
     * @return array<HealthIssue>
     */
    protected function evaluatePermissions(string $pluginSlug, array $deductionRules): array
    {
        $issues = [];
        $permissions = $this->permissionService->getPermissions($pluginSlug);

        // When permissions are not defined
        if ($permissions === null) {
            $issues[] = new HealthIssue(
                type: 'permission_undefined',
                severity: 'warning',
                description: __('services/plugin/plugin_health_scorer.permissions_not_defined_in_json'),
                deduction: $deductionRules['permission_undefined'] ?? -10,
            );

            return $issues;
        }

        // Get inconsistencies from audit results
        $audit = PluginAudit::getBySlug($pluginSlug);
        if ($audit === null || empty($audit->mismatches)) {
            return $issues;
        }

        // Retrieve _optional permissions (no penalty even when not detected)
        $optionalPermissions = $this->permissionService->getOptionalPermissions($pluginSlug);

        foreach ($audit->mismatches as $mismatch) {
            $type = $mismatch['type'] ?? '';
            $permission = $mismatch['permission'] ?? '';

            if ($type === 'undeclared_usage') {
                $isMajor = $this->isHighRiskPermission($permission);
                $issueType = $isMajor ? 'permission_undeclared_major' : 'permission_undeclared_minor';

                $issues[] = new HealthIssue(
                    type: $issueType,
                    severity: $isMajor ? 'critical' : 'warning',
                    description: __('services/plugin/plugin_health_scorer.undeclared_permission_usage', ['permission' => $permission]),
                    evidence: $mismatch['evidence'] ?? [],
                    deduction: $deductionRules[$issueType] ?? ($isMajor ? -15 : -5),
                );
            } elseif ($type === 'unused_declaration') {
                // _optional permissions are exempt from penalties even when unused
                if (in_array($permission, $optionalPermissions, true)) {
                    continue;
                }

                $issues[] = new HealthIssue(
                    type: 'permission_unused',
                    severity: 'info',
                    description: __('services/plugin/plugin_health_scorer.unused_permission_declaration').$permission,
                    deduction: $deductionRules['permission_unused'] ?? -2,
                );
            }
        }

        return $issues;
    }

    /**
     * Evaluate CSP compliance
     *
     * @return array<HealthIssue>
     */
    protected function evaluateCsp(string $pluginSlug, array $deductionRules): array
    {
        $issues = [];
        $audit = PluginAudit::getBySlug($pluginSlug);

        if ($audit === null) {
            return $issues;
        }

        // When inline CSS is required
        if ($audit->csp_requires_inline_css) {
            $issues[] = new HealthIssue(
                type: 'csp_inline_css_required',
                severity: 'info',
                description: __('services/plugin/plugin_health_scorer.inline_css_required_strict_mode'),
                deduction: $deductionRules['csp_inline_css_required'] ?? -5,
            );
        }

        // When inline JS is required
        if ($audit->csp_requires_inline_js) {
            $issues[] = new HealthIssue(
                type: 'csp_inline_js_required',
                severity: 'warning',
                description: __('services/plugin/plugin_health_scorer.inline_js_required_strict_mode'),
                deduction: $deductionRules['csp_inline_js_required'] ?? -10,
            );
        }

        // Evaluate based on CSP status
        if ($audit->csp_status === 'inline_required') {
            $cspMode = config('dixlase.security.csp_mode', 'standard');
            $issueType = match ($cspMode) {
                'strict' => 'csp_violation_strict',
                'standard' => 'csp_violation_standard',
                default => 'csp_violation_dev',
            };

            $deduction = $deductionRules[$issueType] ?? 0;
            if ($deduction !== 0) {
                $issues[] = new HealthIssue(
                    type: $issueType,
                    severity: $cspMode === 'strict' ? 'critical' : 'warning',
                    description: __('services/plugin/plugin_health_scorer.csp_violation_detected', ['cspMode' => $cspMode]),
                    deduction: $deduction,
                );
            }
        }

        return $issues;
    }

    /**
     * Evaluate dangerous API detection
     *
     * @return array<HealthIssue>
     */
    protected function evaluateDangerousApis(string $pluginSlug, array $deductionRules): array
    {
        $issues = [];
        $audit = PluginAudit::getBySlug($pluginSlug);

        if ($audit === null || empty($audit->mismatches)) {
            return $issues;
        }

        // Check dangerous API detection from audit results
        foreach ($audit->mismatches as $mismatch) {
            $permission = $mismatch['permission'] ?? '';
            $evidence = $mismatch['evidence'] ?? [];

            // Use of dangerous APIs (exec/shell_exec, etc.)
            if ($this->isDangerousApiPermission($permission)) {
                $issues[] = new HealthIssue(
                    type: 'dangerous_api_exec',
                    severity: 'critical',
                    description: __('services/plugin/plugin_health_scorer.dangerous_api_detected', ['permission' => $permission]),
                    evidence: $evidence,
                    deduction: $deductionRules['dangerous_api_exec'] ?? -30,
                );
            }
        }

        return $issues;
    }

    /**
     * Evaluate scan freshness
     *
     * @return array<HealthIssue>
     */
    protected function evaluateScanFreshness(string $pluginSlug, array $deductionRules): array
    {
        $issues = [];
        $audit = PluginAudit::getBySlug($pluginSlug);

        if ($audit === null || $audit->audited_at === null) {
            $issues[] = new HealthIssue(
                type: 'scan_not_performed',
                severity: 'warning',
                description: __('services/plugin/plugin_health_scorer.audit_scan_not_run'),
                deduction: $deductionRules['scan_not_performed'] ?? -10,
            );

            return $issues;
        }

        $daysSinceScan = $audit->audited_at->diffInDays(now());
        if ($daysSinceScan > self::SCAN_EXPIRY_DAYS) {
            $issues[] = new HealthIssue(
                type: 'scan_outdated',
                severity: 'info',
                description: __('services/plugin/plugin_health_scorer.scan_outdated_rescan_recommended', ['daysSinceScan' => $daysSinceScan]),
                deduction: $deductionRules['scan_outdated'] ?? -5,
            );
        }

        return $issues;
    }

    /**
     * Evaluate risky permissions (reflect deductions for public_uploads, etc. in health score)
     *
     * @return HealthIssue[]
     */
    protected function evaluateRiskPermissions(array $permissions, array $deductionRules): array
    {
        $issues = [];

        if ($permissions['storage']['public_uploads'] ?? false) {
            if ($permissions['storage']['own_directory'] ?? false) {
                $issues[] = new HealthIssue(
                    type: 'risk_public_uploads_own_dir',
                    severity: 'info',
                    description: __('services/plugin/plugin_health_scorer.public_upload_in_dedicated_dir'),
                    deduction: $deductionRules['risk_public_uploads_own_dir'] ?? -2,
                );
            } else {
                $issues[] = new HealthIssue(
                    type: 'risk_public_uploads_no_own_dir',
                    severity: 'warning',
                    description: __('services/plugin/plugin_health_scorer.direct_upload_to_public_dir'),
                    deduction: $deductionRules['risk_public_uploads_no_own_dir'] ?? -4,
                );
            }
        }

        if ($permissions['members']['delete'] ?? false) {
            $issues[] = new HealthIssue(
                type: 'risk_members_delete',
                severity: 'warning',
                description: __('services/plugin/plugin_health_scorer.uses_member_deletion_permission'),
                deduction: $deductionRules['risk_members_delete'] ?? -4,
            );
        }

        if ($permissions['mail']['bulk_send'] ?? false) {
            $issues[] = new HealthIssue(
                type: 'risk_mail_bulk_send',
                severity: 'warning',
                description: __('services/plugin/plugin_health_scorer.uses_bulk_email_permission'),
                deduction: $deductionRules['risk_mail_bulk_send'] ?? -3,
            );
        }

        return $issues;
    }

    /**
     * Evaluate supply chain defense metadata (author_id / authority_key_id)
     *
     * Record as health_issue when required metadata is missing in plugin.json
     *
     * @return array<HealthIssue>
     */
    protected function evaluateSupplyChainMetadata(string $pluginSlug, array $deductionRules): array
    {
        $issues = [];

        $pluginName = \Illuminate\Support\Str::studly(str_replace('-', '_', $pluginSlug));
        $pluginJsonPath = base_path("plugins/{$pluginName}/plugin.json");

        if (! \Illuminate\Support\Facades\File::exists($pluginJsonPath)) {
            return $issues;
        }

        try {
            $data = json_decode(\Illuminate\Support\Facades\File::get($pluginJsonPath), true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
                return $issues;
            }
        } catch (\Throwable $e) {
            return $issues;
        }

        if (empty($data['author_id'])) {
            $issues[] = new HealthIssue(
                type: 'missing_author_id',
                severity: 'warning',
                description: __('services/plugin/plugin_health_scorer.author_id_not_defined'),
                deduction: $deductionRules['missing_author_id'] ?? -3,
            );
        }

        if (empty($data['authority_key_id'])) {
            $issues[] = new HealthIssue(
                type: 'missing_authority_key_id',
                severity: 'warning',
                description: __('services/plugin/plugin_health_scorer.authority_key_id_not_defined'),
                deduction: $deductionRules['missing_authority_key_id'] ?? -3,
            );
        }

        return $issues;
    }

    /**
     * Whether critical issues are included
     */
    protected function hasCriticalIssue(array $issues): bool
    {
        foreach ($issues as $issue) {
            if ($issue->isCritical() && PluginHealthStatus::isCriticalIssue($issue->type)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Whether it is a high-risk permission
     */
    protected function isHighRiskPermission(string $permission): bool
    {
        $highRiskPermissions = [
            'database.core_tables_write',
            'members.write',
            'members.delete',
            'system.modify_routes',
        ];

        return in_array($permission, $highRiskPermissions, true);
    }

    /**
     * Whether it is a permission related to dangerous APIs
     */
    protected function isDangerousApiPermission(string $permission): bool
    {
        return str_starts_with($permission, 'dangerous_api.');
    }

    /**
     * Determine activation action based on health score and security settings
     *
     * Allowed by security settings (extension_plugin_max_health_level)
     * If within health level, can be enabled with confirmation even if critical issues exist
     * Block only if status is outside allowed range
     *
     * Exception: when the active extension security preset is Development,
     * health-based gating is bypassed (max level forced to NotVerified) so
     * low-scoring plugins can still be installed/enabled with a warning.
     */
    public function determineEnableAction(HealthScoreResult $result, ?ExtensionSecurityLevel $maxAllowedLevel = null): PluginEnableAction
    {
        if ($maxAllowedLevel === null) {
            $preset = (string) SecuritySettingsRegistry::get(
                'extension_security_preset',
                ExtensionSecurityPreset::default()->value,
            );

            if ($preset === ExtensionSecurityPreset::Development->value) {
                $maxAllowedLevel = ExtensionSecurityLevel::NotVerified;
            } else {
                $maxAllowedLevel = ExtensionSecurityLevel::from(
                    (int) SecuritySettingsRegistry::get('extension_plugin_max_health_level', ExtensionSecurityLevel::Warning->value)
                );
            }
        }

        // Block status not allowed in security settings
        if (! $result->status->canActivate($maxAllowedLevel)) {
            return PluginEnableAction::Blocked;
        }

        // If allowed in security settings, determine based on score
        if ($result->hasCriticalIssue || $result->score < 50) {
            return PluginEnableAction::AcknowledgementRequired;
        }

        return match (true) {
            $result->score >= 90 => PluginEnableAction::Allowed,
            $result->score >= 70 => PluginEnableAction::WarningRequired,
            default => PluginEnableAction::AcknowledgementRequired,
        };
    }

    /**
     * Calculate hash of plugin code files (for rescan detection)
     */
    public function computeFilesHash(string $pluginSlug): string
    {
        $pluginName = Str::studly(str_replace('-', '_', $pluginSlug));
        $pluginPath = base_path("plugins/{$pluginName}");

        if (! File::isDirectory($pluginPath)) {
            return '';
        }

        $hashes = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($pluginPath, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS),
        );
        // Prevent infinite recursion from circular directory structures
        $iterator->setMaxDepth(20);

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $ext = $file->getExtension();
            $filename = $file->getFilename();

            // Target PHP, JS, Blade files
            if ($ext === 'php' || $ext === 'js' || str_ends_with($filename, '.blade.php')) {
                $hashes[] = md5_file($file->getPathname());
            }
        }

        sort($hashes);

        return md5(implode('', $hashes));
    }

    /**
     * Whether the plugin requires a rescan
     */
    public function needsRescan(string $pluginSlug): bool
    {
        $audit = PluginAudit::getBySlug($pluginSlug);

        if ($audit === null || $audit->files_hash === null) {
            return true;
        }

        $currentHash = $this->computeFilesHash($pluginSlug);

        return $currentHash !== $audit->files_hash;
    }

    /**
     * Fast detection that retrieves the last modified time (mtime) of plugin source
     *
     * Unlike computeFilesHash(), only retrieves mtime without calculating md5
     * For file change detection during page display
     *
     * Returns null if the plugin directory does not exist.
     */
    public function latestSourceMtime(string $pluginSlug): ?int
    {
        $pluginName = Str::studly(str_replace('-', '_', $pluginSlug));
        $pluginPath = base_path("plugins/{$pluginName}");

        if (! File::isDirectory($pluginPath)) {
            return null;
        }

        $latest = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($pluginPath, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS),
        );
        $iterator->setMaxDepth(20);

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $ext = $file->getExtension();
            $filename = $file->getFilename();

            if ($ext === 'php' || $ext === 'js' || str_ends_with($filename, '.blade.php')) {
                $mtime = $file->getMTime();
                if ($mtime > $latest) {
                    $latest = $mtime;
                }
            }
        }

        return $latest > 0 ? $latest : null;
    }
}
