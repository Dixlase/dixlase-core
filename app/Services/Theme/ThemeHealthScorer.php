<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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
use App\Enums\PluginHealthStatus;
use App\Models\ThemeAudit;

/**
 * テーマ健全性スコアの計算元
 *
 * PluginHealthStatus::getDeductionRules() を減点テーブルとして使用し、
 * 署名検証・権限整合性・CSP適合性・危険API検出・スキャン鮮度を評価して
 * 0-100点のスコアと健全性ステータスを返します。
 *
 * @internal コア専用。プラグイン/テーマから参照しないこと
 */
class ThemeHealthScorer
{
    /**
     * 初期スコア
     */
    protected const BASE_SCORE = 100;

    /**
     * スキャン期限切れとみなす日数
     */
    protected const SCAN_EXPIRY_DAYS = 30;

    public function __construct(
        protected ThemePermissionService $permissionService,
    ) {}

    /**
     * テーマの健全性スコアを計算
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
                    description: '監査スキャンが未実行です。',
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
                description: 'permissions セクションが未定義です。',
                deduction: $deductionRules['permission_undefined'] ?? -10,
            );
        }

        // 1. 署名検証の評価
        $issues = array_merge($issues, $this->evaluateSignature($themeSlug, $deductionRules));

        // 2. 権限整合性の評価
        if ($permissions !== null) {
            $issues = array_merge($issues, $this->evaluatePermissions($themeSlug, $deductionRules));
        }

        // 3. CSP適合性の評価
        $issues = array_merge($issues, $this->evaluateCsp($themeSlug, $deductionRules));

        // 4. 危険API検出の評価
        $issues = array_merge($issues, $this->evaluateDangerousApis($themeSlug, $deductionRules));

        // 5. スキャン鮮度の評価
        $issues = array_merge($issues, $this->evaluateScanFreshness($themeSlug, $deductionRules));

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
     * 署名検証の評価
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
                description: '署名がありません。配布時は署名を推奨します。',
                deduction: $deductionRules['signature_unsigned'] ?? -10,
            );
        } elseif ($signatureInfo['status'] === 'invalid') {
            $issues[] = new HealthIssue(
                type: 'signature_invalid',
                severity: 'critical',
                description: '署名が無効です。改ざんの可能性があります。',
                deduction: $deductionRules['signature_invalid'] ?? -50,
            );
        }

        return $issues;
    }

    /**
     * 権限整合性の評価
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
                    description: "未宣言の権限使用: {$permission}",
                    evidence: $mismatch['evidence'] ?? [],
                    deduction: $deductionRules[$issueType] ?? ($isMajor ? -15 : -5),
                );
            } elseif ($type === 'unused_declaration') {
                $issues[] = new HealthIssue(
                    type: 'permission_unused',
                    severity: 'info',
                    description: '未使用の権限宣言: '.$permission,
                    deduction: $deductionRules['permission_unused'] ?? -2,
                );
            }
        }

        return $issues;
    }

    /**
     * CSP適合性の評価
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
                description: 'インラインCSSが必要です。厳格モードでは動作しない可能性があります。',
                deduction: $deductionRules['csp_inline_css_required'] ?? -5,
            );
        }

        if ($audit->csp_requires_inline_js) {
            $issues[] = new HealthIssue(
                type: 'csp_inline_js_required',
                severity: 'warning',
                description: 'インラインJavaScriptが必要です。厳格モードでは動作しません。',
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
                    description: "CSP違反が検出されました（{$cspMode}モード）。",
                    deduction: $deduction,
                );
            }
        }

        return $issues;
    }

    /**
     * 危険API検出の評価
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
                    description: "危険なAPIが検出されました: {$permission}",
                    evidence: $evidence,
                    deduction: $deductionRules['dangerous_api_exec'] ?? -30,
                );
            }
        }

        return $issues;
    }

    /**
     * スキャン鮮度の評価
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
                description: '監査スキャンが実行されていません。',
                deduction: $deductionRules['scan_not_performed'] ?? -10,
            );

            return $issues;
        }

        $daysSinceScan = $audit->audited_at->diffInDays(now());
        if ($daysSinceScan > self::SCAN_EXPIRY_DAYS) {
            $issues[] = new HealthIssue(
                type: 'scan_outdated',
                severity: 'info',
                description: "スキャンが古くなっています（{$daysSinceScan}日前）。再スキャンを推奨します。",
                deduction: $deductionRules['scan_outdated'] ?? -5,
            );
        }

        return $issues;
    }

    /**
     * 致命的な問題が含まれているか
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
     * 高リスク権限かどうか
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
}
