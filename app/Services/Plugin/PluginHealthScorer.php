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

namespace App\Services\Plugin;

use App\DTO\Plugin\HealthIssue;
use App\DTO\Plugin\HealthScoreResult;
use App\Enums\ExtensionSecurityLevel;
use App\Enums\PluginEnableAction;
use App\Enums\PluginHealthStatus;
use App\Models\PluginAudit;
use App\Services\SecuritySettingsRegistry;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * プラグイン健全性スコアの唯一の計算元
 *
 * PluginHealthStatus::getDeductionRules() を減点テーブルとして使用し、
 * 署名検証・権限整合性・CSP適合性・危険API検出・スキャン鮮度を評価して
 * 0-100点のスコアと健全性ステータスを返します。
 *
 * @internal コア専用。プラグイン/テーマから参照しないこと
 */
class PluginHealthScorer
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
        protected PluginPermissionService $permissionService,
    ) {}

    /**
     * プラグインの健全性スコアを計算
     */
    public function calculate(string $pluginSlug): HealthScoreResult
    {
        // 前提条件チェック: NotVerified判定
        $audit = PluginAudit::getBySlug($pluginSlug);
        $permissions = $this->permissionService->getPermissions($pluginSlug);

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

        if ($permissions === null) {
            return new HealthScoreResult(
                score: 0,
                status: PluginHealthStatus::NotVerified,
                issues: [new HealthIssue(
                    type: 'not_verified_no_permissions',
                    severity: 'warning',
                    description: 'permissions セクションが未定義です。',
                    deduction: 0,
                )],
                hasCriticalIssue: false,
            );
        }

        $deductionRules = PluginHealthStatus::getDeductionRules();
        $issues = [];

        // 1. 署名検証の評価
        $issues = array_merge($issues, $this->evaluateSignature($pluginSlug, $deductionRules));

        // 2. 権限整合性の評価
        $issues = array_merge($issues, $this->evaluatePermissions($pluginSlug, $deductionRules));

        // 3. CSP適合性の評価
        $issues = array_merge($issues, $this->evaluateCsp($pluginSlug, $deductionRules));

        // 4. 危険API検出の評価
        $issues = array_merge($issues, $this->evaluateDangerousApis($pluginSlug, $deductionRules));

        // 5. スキャン鮮度の評価
        $issues = array_merge($issues, $this->evaluateScanFreshness($pluginSlug, $deductionRules));

        // 合計スコアの算出
        $totalDeduction = array_sum(array_map(fn (HealthIssue $i) => $i->deduction, $issues));
        $score = max(0, self::BASE_SCORE + $totalDeduction);

        // 致命的問題の判定
        $hasCriticalIssue = $this->hasCriticalIssue($issues);

        // ステータスの決定
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
    protected function evaluateSignature(string $pluginSlug, array $deductionRules): array
    {
        $issues = [];
        $signatureInfo = $this->permissionService->getSignatureInfo($pluginSlug);

        if ($signatureInfo['status'] === 'unsigned') {
            $isProduction = app()->environment('production');
            $deduction = $isProduction
                ? ($deductionRules['signature_unsigned_production'] ?? -15)
                : ($deductionRules['signature_unsigned'] ?? -5);

            $issues[] = new HealthIssue(
                type: $isProduction ? 'signature_unsigned_production' : 'signature_unsigned',
                severity: $isProduction ? 'warning' : 'info',
                description: $isProduction
                    ? '本番環境で署名がありません。署名を強く推奨します。'
                    : '署名がありません。本番配布時は署名を推奨します。',
                deduction: $deduction,
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
    protected function evaluatePermissions(string $pluginSlug, array $deductionRules): array
    {
        $issues = [];
        $permissions = $this->permissionService->getPermissions($pluginSlug);

        // 権限が定義されていない場合
        if ($permissions === null) {
            $issues[] = new HealthIssue(
                type: 'permission_undefined',
                severity: 'warning',
                description: 'plugin.json に permissions セクションが定義されていません。',
                deduction: $deductionRules['permission_undefined'] ?? -10,
            );

            return $issues;
        }

        // 監査結果から不一致を取得
        $audit = PluginAudit::getBySlug($pluginSlug);
        if ($audit === null || empty($audit->mismatches)) {
            return $issues;
        }

        // _optional 権限を取得（未検出でもペナルティなし）
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
                    description: "未宣言の権限使用: {$permission}",
                    evidence: $mismatch['evidence'] ?? [],
                    deduction: $deductionRules[$issueType] ?? ($isMajor ? -15 : -5),
                );
            } elseif ($type === 'unused_declaration') {
                // _optional に含まれる権限は未使用でもペナルティなし
                if (in_array($permission, $optionalPermissions, true)) {
                    continue;
                }

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
    protected function evaluateCsp(string $pluginSlug, array $deductionRules): array
    {
        $issues = [];
        $audit = PluginAudit::getBySlug($pluginSlug);

        if ($audit === null) {
            return $issues;
        }

        // インラインCSS必須の場合
        if ($audit->csp_requires_inline_css) {
            $issues[] = new HealthIssue(
                type: 'csp_inline_css_required',
                severity: 'info',
                description: 'インラインCSSが必要です。厳格モードでは動作しない可能性があります。',
                deduction: $deductionRules['csp_inline_css_required'] ?? -5,
            );
        }

        // インラインJS必須の場合
        if ($audit->csp_requires_inline_js) {
            $issues[] = new HealthIssue(
                type: 'csp_inline_js_required',
                severity: 'warning',
                description: 'インラインJavaScriptが必要です。厳格モードでは動作しません。',
                deduction: $deductionRules['csp_inline_js_required'] ?? -10,
            );
        }

        // CSPステータスに基づく評価
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
    protected function evaluateDangerousApis(string $pluginSlug, array $deductionRules): array
    {
        $issues = [];
        $audit = PluginAudit::getBySlug($pluginSlug);

        if ($audit === null || empty($audit->mismatches)) {
            return $issues;
        }

        // 監査結果から危険APIの検出を確認
        foreach ($audit->mismatches as $mismatch) {
            $permission = $mismatch['permission'] ?? '';
            $evidence = $mismatch['evidence'] ?? [];

            // 危険なAPI（exec/shell_exec等）の使用
            if ($this->isDangerousApiPermission($permission)) {
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
    protected function evaluateScanFreshness(string $pluginSlug, array $deductionRules): array
    {
        $issues = [];
        $audit = PluginAudit::getBySlug($pluginSlug);

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
            'members.write',
            'members.delete',
            'system.modify_routes',
        ];

        return in_array($permission, $highRiskPermissions, true);
    }

    /**
     * 危険なAPIに関連する権限かどうか
     */
    protected function isDangerousApiPermission(string $permission): bool
    {
        return str_starts_with($permission, 'dangerous_api.');
    }

    /**
     * 健全性スコアとセキュリティ設定に基づいて有効化アクションを判定
     *
     * セキュリティ設定（extension_plugin_max_health_level）で許可された
     * 健全性レベル内であれば、致命的問題があっても確認付きで有効化可能。
     * 許可範囲外のステータスの場合のみブロックする。
     */
    public function determineEnableAction(HealthScoreResult $result, ?ExtensionSecurityLevel $maxAllowedLevel = null): PluginEnableAction
    {
        if ($maxAllowedLevel === null) {
            $maxAllowedLevel = ExtensionSecurityLevel::from(
                (int) SecuritySettingsRegistry::get('extension_plugin_max_health_level', ExtensionSecurityLevel::Warning->value)
            );
        }

        // セキュリティ設定で許可されていないステータスはブロック
        if (! $result->status->canActivate($maxAllowedLevel)) {
            return PluginEnableAction::Blocked;
        }

        // セキュリティ設定で許可されている場合はスコアに基づいて判定
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
     * プラグインのコードファイルのハッシュを計算（再スキャン判定用）
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
            new RecursiveDirectoryIterator($pluginPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $ext = $file->getExtension();
            $filename = $file->getFilename();

            // PHP, JS, Blade ファイルを対象
            if ($ext === 'php' || $ext === 'js' || str_ends_with($filename, '.blade.php')) {
                $hashes[] = md5_file($file->getPathname());
            }
        }

        sort($hashes);

        return md5(implode('', $hashes));
    }

    /**
     * プラグインが再スキャンを必要とするかどうか
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
}
