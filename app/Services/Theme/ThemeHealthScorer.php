<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Services\Theme;

use App\DTO\Plugin\HealthIssue;
use App\DTO\Plugin\HealthScoreResult;
use App\Enums\ExtensionCompatibilityStatus;
use App\Enums\ExtensionSecurityLevel;
use App\Enums\PluginEnableAction;
use App\Enums\PluginHealthStatus;
use App\Models\ThemeAudit;
use App\Services\Extension\ExtensionCompatibilityChecker;
use App\Services\Extension\ExtensionEnableActionResolver;
use Illuminate\Support\Facades\File;

/**
 * Calculates theme health score
 *
 * Uses PluginHealthStatus::getDeductionRules() as the deduction table,
 * evaluates signature verification, permission consistency, CSP compliance, dangerous API detection, and scan freshness
 * to return a score of 0-100 and health status.
 *
 * @internal Core use only. Do not reference from plugins/themes
 */
class ThemeHealthScorer
{
    /**
     * Initial score
     */
    protected const BASE_SCORE = 100;

    /**
     * Days to consider scan expired
     */
    protected const SCAN_EXPIRY_DAYS = 30;

    public function __construct(
        protected ThemePermissionService $permissionService,
    ) {}

    /**
     * Calculate theme health score
     */
    public function calculate(string $themeSlug): HealthScoreResult
    {
        $audit = ThemeAudit::getBySlug($themeSlug);
        $permissions = $this->permissionService->getPermissions($themeSlug);

        if ($audit === null || $audit->audited_at === null) {
            return new HealthScoreResult(
                score: 0,
                status: PluginHealthStatus::NotVerified,
                issues: [new HealthIssue(
                    type: 'not_verified_no_scan',
                    severity: 'warning',
                    description: __('services/theme/theme_health_scorer.audit_scan_not_executed'),
                    deduction: 0,
                )],
                hasCriticalIssue: false,
            );
        }

        $deductionRules = PluginHealthStatus::getDeductionRules();
        $issues = [];

        if ($permissions === null) {
            $issues[] = new HealthIssue(
                type: 'permission_undefined',
                severity: 'warning',
                description: __('services/theme/theme_health_scorer.permissions_section_undefined'),
                deduction: $deductionRules['permission_undefined'] ?? -10,
            );
        }

        // 1. Evaluate signature verification
        $issues = array_merge($issues, $this->evaluateSignature($themeSlug, $deductionRules));

        // 2. Evaluate permission consistency
        if ($permissions !== null) {
            $issues = array_merge($issues, $this->evaluatePermissions($themeSlug, $deductionRules));
        }

        // 3. Evaluate CSP compliance
        $issues = array_merge($issues, $this->evaluateCsp($themeSlug, $deductionRules));

        // 4. Evaluate dangerous API detection
        $issues = array_merge($issues, $this->evaluateDangerousApis($themeSlug, $deductionRules));

        // 5. Evaluate scan freshness
        $issues = array_merge($issues, $this->evaluateScanFreshness($themeSlug, $deductionRules));

        // 6. Extension API contract version evaluation (requires.dixlase_api)
        $issues = array_merge($issues, $this->evaluateApiCompatibility($themeSlug, $deductionRules));

        $totalDeduction = array_sum(array_map(fn (HealthIssue $i) => $i->deduction, $issues));
        $score = max(0, self::BASE_SCORE + $totalDeduction);

        $hasCriticalIssue = $this->hasCriticalIssue($issues);
        $status = PluginHealthStatus::fromScore($score, $hasCriticalIssue);

        return new HealthScoreResult(
            score: $score,
            status: $status,
            issues: $issues,
            hasCriticalIssue: $hasCriticalIssue,
        );
    }

    /**
     * Determine the activation action for a theme from its health score.
     *
     * Mirrors PluginHealthScorer::determineEnableAction(); both delegate to
     * ExtensionEnableActionResolver, which applies the theme-specific
     * `extension_theme_max_health_level` gate.
     */
    public function determineEnableAction(HealthScoreResult $result, ?ExtensionSecurityLevel $maxAllowedLevel = null): PluginEnableAction
    {
        return (new ExtensionEnableActionResolver())->resolve($result, 'theme', $maxAllowedLevel);
    }

    /**
     * Evaluate signature verification
     *
     * @return array<HealthIssue>
     */
    protected function evaluateSignature(string $themeSlug, array $deductionRules): array
    {
        $issues = [];
        $signatureInfo = $this->permissionService->getSignatureInfo($themeSlug);

        if ($signatureInfo['status'] === 'unsigned') {
            $issues[] = new HealthIssue(
                type: 'signature_unsigned',
                severity: 'warning',
                description: __('services/theme/theme_health_scorer.no_signature_recommend_signing'),
                deduction: $deductionRules['signature_unsigned'] ?? -10,
            );
        } elseif ($signatureInfo['status'] === 'invalid') {
            $issues[] = new HealthIssue(
                type: 'signature_invalid',
                severity: 'critical',
                description: __('services/theme/theme_health_scorer.invalid_signature_tampering'),
                deduction: $deductionRules['signature_invalid'] ?? -50,
            );
        }

        return $issues;
    }

    /**
     * Evaluate permission consistency
     *
     * @return array<HealthIssue>
     */
    protected function evaluatePermissions(string $themeSlug, array $deductionRules): array
    {
        $issues = [];
        $permissions = $this->permissionService->getPermissions($themeSlug);

        if ($permissions === null) {
            return $issues;
        }

        $audit = ThemeAudit::getBySlug($themeSlug);
        if ($audit === null || empty($audit->mismatches)) {
            return $issues;
        }

        foreach ($audit->mismatches as $mismatch) {
            $type = $mismatch['type'] ?? '';
            $permission = $mismatch['permission'] ?? '';

            if ($type === 'undeclared_usage') {
                $isMajor = $this->isHighRiskPermission($permission);
                $issueType = $isMajor ? 'permission_undeclared_major' : 'permission_undeclared_minor';

                $issues[] = new HealthIssue(
                    type: $issueType,
                    severity: $isMajor ? 'critical' : 'warning',
                    description: __('services/theme/theme_health_scorer.undeclared_permission_used', ['permission' => $permission]),
                    evidence: $mismatch['evidence'] ?? [],
                    deduction: $deductionRules[$issueType] ?? ($isMajor ? -15 : -5),
                );
            } elseif ($type === 'unused_declaration') {
                $issues[] = new HealthIssue(
                    type: 'permission_unused',
                    severity: 'info',
                    description: __('services/theme/theme_health_scorer.unused_permission_declaration').$permission,
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
    protected function evaluateCsp(string $themeSlug, array $deductionRules): array
    {
        $issues = [];
        $audit = ThemeAudit::getBySlug($themeSlug);

        if ($audit === null) {
            return $issues;
        }

        if ($audit->csp_requires_inline_css) {
            $issues[] = new HealthIssue(
                type: 'csp_inline_css_required',
                severity: 'info',
                description: __('services/theme/theme_health_scorer.inline_css_strict_mode_warning'),
                deduction: $deductionRules['csp_inline_css_required'] ?? -5,
            );
        }

        if ($audit->csp_requires_inline_js) {
            $issues[] = new HealthIssue(
                type: 'csp_inline_js_required',
                severity: 'warning',
                description: __('services/theme/theme_health_scorer.inline_js_strict_mode_error'),
                deduction: $deductionRules['csp_inline_js_required'] ?? -10,
            );
        }

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
                    description: __('services/theme/theme_health_scorer.csp_violation_detected', ['cspMode' => $cspMode]),
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
    protected function evaluateDangerousApis(string $themeSlug, array $deductionRules): array
    {
        $issues = [];
        $audit = ThemeAudit::getBySlug($themeSlug);

        if ($audit === null || empty($audit->mismatches)) {
            return $issues;
        }

        foreach ($audit->mismatches as $mismatch) {
            $permission = $mismatch['permission'] ?? '';
            $evidence = $mismatch['evidence'] ?? [];

            if (str_starts_with($permission, 'dangerous_api.')) {
                $issues[] = new HealthIssue(
                    type: 'dangerous_api_exec',
                    severity: 'critical',
                    description: __('services/theme/theme_health_scorer.dangerous_api_detected', ['permission' => $permission]),
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
    protected function evaluateScanFreshness(string $themeSlug, array $deductionRules): array
    {
        $issues = [];
        $audit = ThemeAudit::getBySlug($themeSlug);

        if ($audit === null || $audit->audited_at === null) {
            $issues[] = new HealthIssue(
                type: 'scan_not_performed',
                severity: 'warning',
                description: __('services/theme/theme_health_scorer.audit_scan_not_run'),
                deduction: $deductionRules['scan_not_performed'] ?? -10,
            );

            return $issues;
        }

        $daysSinceScan = $audit->audited_at->diffInDays(now());
        if ($daysSinceScan > self::SCAN_EXPIRY_DAYS) {
            $issues[] = new HealthIssue(
                type: 'scan_outdated',
                severity: 'info',
                description: __('services/theme/theme_health_scorer.scan_outdated_rescan_recommended', ['daysSinceScan' => $daysSinceScan]),
                deduction: $deductionRules['scan_outdated'] ?? -5,
            );
        }

        return $issues;
    }

    /**
     * Evaluate Extension API contract version declaration
     *
     * Reads requires.dixlase_api from theme.json and runs it through
     * ExtensionCompatibilityChecker. Emits HealthIssue types:
     *   - missing_api_version       — field absent
     *   - incompatible_api_version  — declared range excludes core version
     *   - malformed_api_constraint  — value is not a valid semver constraint
     *
     * @return array<HealthIssue>
     */
    protected function evaluateApiCompatibility(string $themeSlug, array $deductionRules): array
    {
        $themeName = \App\Models\Theme::directoryNameFromSlug($themeSlug);
        $themeJsonPath = base_path("themes/{$themeName}/theme.json");

        if (! File::exists($themeJsonPath)) {
            return [];
        }

        try {
            $data = json_decode(File::get($themeJsonPath), true);
            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
                return [];
            }
        } catch (\Throwable) {
            return [];
        }

        $result = (new ExtensionCompatibilityChecker())->check($data);

        return match ($result->status) {
            ExtensionCompatibilityStatus::MissingDeclaration => [new HealthIssue(
                type: 'missing_api_version',
                severity: 'warning',
                description: __('services/theme/theme_health_scorer.api_version_missing'),
                deduction: $deductionRules['missing_api_version'] ?? -5,
            )],
            ExtensionCompatibilityStatus::Incompatible => [new HealthIssue(
                type: 'incompatible_api_version',
                severity: 'warning',
                description: __('services/theme/theme_health_scorer.api_version_incompatible', [
                    'declared' => $result->declared ?? '',
                    'supported' => $result->coreVersion,
                ]),
                deduction: $deductionRules['incompatible_api_version'] ?? -15,
            )],
            ExtensionCompatibilityStatus::MalformedConstraint => [new HealthIssue(
                type: 'malformed_api_constraint',
                severity: 'warning',
                description: __('services/theme/theme_health_scorer.api_constraint_malformed'),
                deduction: $deductionRules['malformed_api_constraint'] ?? -10,
            )],
            ExtensionCompatibilityStatus::Compatible => [],
        };
    }

    /**
     * Whether critical issues are present
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
     * Whether high-risk permission
     */
    protected function isHighRiskPermission(string $permission): bool
    {
        $highRiskPermissions = [
            'database.core_tables_write',
            'system.modify_routes',
            'system.register_middleware',
        ];

        return in_array($permission, $highRiskPermissions, true);
    }

    /**
     * Calculate hash of theme code files (for rescan detection)
     */
    public function computeFilesHash(string $themeSlug): string
    {
        $themePath = base_path("themes/{$themeSlug}");

        if (! \Illuminate\Support\Facades\File::isDirectory($themePath)) {
            return '';
        }

        $hashes = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($themePath, \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveDirectoryIterator::FOLLOW_SYMLINKS),
        );
        $iterator->setMaxDepth(20);

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $ext = $file->getExtension();
            $filename = $file->getFilename();

            if ($ext === 'php' || $ext === 'js' || str_ends_with($filename, '.blade.php')) {
                $hashes[] = md5_file($file->getPathname());
            }
        }

        sort($hashes);

        return md5(implode('', $hashes));
    }

    /**
     * Fast detection that retrieves the last modified time (mtime) of theme source
     *
     * Unlike computeFilesHash(), only retrieves mtime without performing md5 calculation
     * For detecting file changes during page display
     */
    public function latestSourceMtime(string $themeSlug): ?int
    {
        $themePath = base_path("themes/{$themeSlug}");

        if (! \Illuminate\Support\Facades\File::isDirectory($themePath)) {
            return null;
        }

        $latest = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($themePath, \RecursiveDirectoryIterator::SKIP_DOTS | \RecursiveDirectoryIterator::FOLLOW_SYMLINKS),
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
