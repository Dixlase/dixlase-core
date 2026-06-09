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

namespace App\Enums;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * Plugin/theme health status
 *
 * Health represents the internal consistency and state
 * Evaluates the match between declared permissions and actual behavior, signature validity, CSP compliance, etc.
 *
 * Scoring rules:
 * - Initial score: 100
 * - Deduct points for each issue
 * - 90-100: Healthy
 * - 70-89: Advisory
 * - 0-69: NeedsAttention
 * - Critical flag: immediately NeedsAttention
 */
enum PluginHealthStatus: string
{
    /**
     * Healthy - declarations match reality, signature OK, CSP compliant
     */
    case Healthy = 'healthy';

    /**
     * Advisory - minor issues present (unsigned, minor mismatches, etc.)
     */
    case Advisory = 'advisory';

    /**
     * NeedsAttention - significant issues present (signature mismatch, major permission discrepancies)
     */
    case NeedsAttention = 'needs_attention';

    /**
     * Unknown - insufficient information (not scanned, permissions undefined)
     */
    case NotVerified = 'not_verified';

    /**
     * Determine health status from score
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
     * Get translation key
     */
    public function translationKey(): string
    {
        return 'admin/settings/plugins/index.health_status.'.$this->value;
    }

    /**
     * Get label
     */
    public function label(): string
    {
        return __($this->translationKey());
    }

    /**
     * Get description
     */
    public function description(): string
    {
        return __($this->translationKey().'_description');
    }

    /**
     * Get tooltip
     */
    public function tooltip(): string
    {
        return __($this->translationKey().'_tooltip');
    }

    /**
     * Get CSS class (for badge)
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
     * Get icon class
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
     * Get color name
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
     * Whether it can be enabled (based on security settings)
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
     * Whether it can be installed
     */
    public function canInstall(ExtensionSecurityLevel $maxAllowedLevel): bool
    {
        return $this->canActivate($maxAllowedLevel);
    }

    /**
     * Get all statuses
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * Get deduction rules
     */
    public static function getDeductionRules(): array
    {
        return [
            // Signature related
            'signature_unsigned' => -10,
            'signature_invalid' => -50,
            'signature_mismatch' => -50,
            'signature_pending_verification' => -5,
            'signature_unknown_key' => -15,
            'signature_expired' => -20,
            'signature_error' => -10,

            // Metadata for supply chain defense
            'missing_author_id' => -3,
            'missing_authority_key_id' => -3,

            // Extension API contract version (requires.dixlase_api)
            'missing_api_version' => -5,
            'incompatible_api_version' => -15,
            'malformed_api_constraint' => -10,

            // License declaration (SPDX whitelist; see config/licensing.php
            // for per-deployment override of these defaults)
            'missing_license' => -10,
            'invalid_license_spdx' => -5,
            'unknown_license' => -3,
            'license_refused' => -25,

            // Permission related
            'permission_undeclared_minor' => -5,
            'permission_undeclared_major' => -15,
            'permission_unused' => -2,
            'permission_undefined' => -10,

            // CSP related (by mode)
            'csp_violation_dev' => 0,
            'csp_violation_standard' => -5,
            'csp_violation_strict' => -15,
            'csp_inline_js_required' => -10,
            'csp_inline_css_required' => -5,
            'csp_external_resources' => -3,

            // Scan related
            'scan_outdated' => -5,
            'scan_not_performed' => -10,

            // Dangerous API
            'dangerous_api_exec' => -30,
            'dangerous_api_env_access' => -20,

            // File placement
            'file_outside_scope' => -20,
        ];
    }

    /**
     * Determine if it is a critical issue
     */
    public static function isCriticalIssue(string $issueType): bool
    {
        $criticalIssues = [
            'signature_invalid',
            'signature_mismatch',
            'dangerous_api_exec',
            'permission_undeclared_major',
            'license_refused',
        ];

        return in_array($issueType, $criticalIssues, true);
    }
}
