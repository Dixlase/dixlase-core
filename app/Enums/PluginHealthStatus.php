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

namespace App\Enums;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * プラグイン・テーマの健全性ステータス
 *
 * 健全性は「中身の整合性・状態」を表します。
 * 宣言された権限と実態の一致、署名の有効性、CSP適合性などを評価します。
 *
 * 点数化ルール:
 * - 初期スコア: 100
 * - 指摘ごとに減点
 * - 90-100: Healthy（健全）
 * - 70-89: Advisory（注意）
 * - 0-69: NeedsAttention（要確認）
 * - 致命的フラグ: 即座にNeedsAttention
 */
enum PluginHealthStatus: string
{
    /**
     * 健全 - 宣言と実態が一致、署名OK、CSP適合
     */
    case Healthy = 'healthy';

    /**
     * 注意 - 軽微な指摘あり（未署名、軽い不一致など）
     */
    case Advisory = 'advisory';

    /**
     * 要確認 - 重要な指摘あり（署名不一致、大きな権限不一致）
     */
    case NeedsAttention = 'needs_attention';

    /**
     * 未確認 - 情報不足（未スキャン、権限未定義）
     */
    case NotVerified = 'not_verified';

    /**
     * スコアから健全性ステータスを判定
     */
    public static function fromScore(int $score, bool $hasCriticalIssue = false): self
    {
        if ($hasCriticalIssue) {
            return self::NeedsAttention;
        }

        return match (true) {
            $score >= 90 => self::Healthy,
            $score >= 70 => self::Advisory,
            $score >= 0 => self::NeedsAttention,
            default => self::NotVerified,
        };
    }

    /**
     * 翻訳キーを取得
     */
    public function translationKey(): string
    {
        return 'admin/settings/plugins.health_status.'.$this->value;
    }

    /**
     * ラベルを取得
     */
    public function label(): string
    {
        return __($this->translationKey());
    }

    /**
     * 説明を取得
     */
    public function description(): string
    {
        return __($this->translationKey().'_description');
    }

    /**
     * ツールチップを取得
     */
    public function tooltip(): string
    {
        return __($this->translationKey().'_tooltip');
    }

    /**
     * CSSクラスを取得（バッジ用）
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Healthy => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            self::Advisory => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            self::NeedsAttention => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
            self::NotVerified => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
        };
    }

    /**
     * アイコンクラスを取得
     */
    public function iconClass(): string
    {
        return match ($this) {
            self::Healthy => 'fas fa-check-circle',
            self::Advisory => 'fas fa-info-circle',
            self::NeedsAttention => 'fas fa-exclamation-circle',
            self::NotVerified => 'fas fa-question-circle',
        };
    }

    /**
     * 色名を取得
     */
    public function colorName(): string
    {
        return match ($this) {
            self::Healthy => 'green',
            self::Advisory => 'yellow',
            self::NeedsAttention => 'orange',
            self::NotVerified => 'gray',
        };
    }

    /**
     * 有効化可能かどうか（セキュリティ設定に基づく）
     */
    public function canActivate(ExtensionSecurityLevel $maxAllowedLevel): bool
    {
        $statusLevel = match ($this) {
            self::Healthy => ExtensionSecurityLevel::Healthy,
            self::Advisory => ExtensionSecurityLevel::Warning,
            self::NeedsAttention => ExtensionSecurityLevel::NeedsAttention,
            self::NotVerified => ExtensionSecurityLevel::NotVerified,
        };

        return $maxAllowedLevel->allows($statusLevel);
    }

    /**
     * インストール可能かどうか
     */
    public function canInstall(ExtensionSecurityLevel $maxAllowedLevel): bool
    {
        return $this->canActivate($maxAllowedLevel);
    }

    /**
     * すべてのステータスを取得
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * 減点ルールを取得
     */
    public static function getDeductionRules(): array
    {
        return [
            // 署名関連
            'signature_unsigned' => -10,
            'signature_invalid' => -50,
            'signature_mismatch' => -50,
            'signature_pending_verification' => -5,
            'signature_unknown_key' => -15,
            'signature_expired' => -20,
            'signature_error' => -10,

            // 権限関連
            'permission_undeclared_minor' => -5,
            'permission_undeclared_major' => -15,
            'permission_unused' => -2,
            'permission_undefined' => -10,

            // CSP関連（モード別）
            'csp_violation_dev' => 0,
            'csp_violation_standard' => -5,
            'csp_violation_strict' => -15,
            'csp_inline_js_required' => -10,
            'csp_inline_css_required' => -5,
            'csp_external_resources' => -3,

            // スキャン関連
            'scan_outdated' => -5,
            'scan_not_performed' => -10,

            // 危険なAPI
            'dangerous_api_exec' => -30,
            'dangerous_api_env_access' => -20,

            // ファイル配置
            'file_outside_scope' => -20,
        ];
    }

    /**
     * 致命的な問題かどうかを判定
     */
    public static function isCriticalIssue(string $issueType): bool
    {
        $criticalIssues = [
            'signature_invalid',
            'signature_mismatch',
            'dangerous_api_exec',
            'permission_undeclared_major',
        ];

        return in_array($issueType, $criticalIssues, true);
    }
}
