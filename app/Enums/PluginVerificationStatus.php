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
 * Plugin/theme verification status
 *
 * Represents verification status of signature and permission definitions
 */
enum PluginVerificationStatus: string
{
    // ========================================
    // Signature status
    // ========================================

    /**
     * Signature OK - signature is valid and verified
     */
    case SignatureValid = 'signature_valid';

    /**
     * Unsigned - no signature
     */
    case SignatureUnsigned = 'signature_unsigned';

    /**
     * Signature mismatch - invalid signature (possible tampering)
     */
    case SignatureInvalid = 'signature_invalid';

    /**
     * Signature pending - signature exists but not verified
     */
    case SignaturePending = 'signature_pending';

    // ========================================
    // Permission definition status
    // ========================================

    /**
     * Permission definition OK - declaration matches actual
     */
    case PermissionOk = 'permission_ok';

    /**
     * Permission undefined - no permissions in plugin.json
     */
    case PermissionUndefined = 'permission_undefined';

    /**
     * Permission mismatch - declaration does not match actual
     */
    case PermissionMismatch = 'permission_mismatch';

    // ========================================
    // Scan status
    // ========================================

    /**
     * Scan not executed
     */
    case ScanNotPerformed = 'scan_not_performed';

    /**
     * Scan expired (e.g., after rule update)
     */
    case ScanOutdated = 'scan_outdated';

    /**
     * Scan completed
     */
    case ScanCompleted = 'scan_completed';

    // ========================================
    // CSP compatibility status
    // ========================================

    /**
     * CSP Ready - fully CSP compliant
     */
    case CspReady = 'csp_ready';

    /**
     * CSP compatible - works with nonce
     */
    case CspCompatible = 'csp_compatible';

    /**
     * Inline JS required - cannot work in strict CSP mode
     */
    case CspInlineRequired = 'csp_inline_required';

    /**
     * CSP not verified
     */
    case CspNotChecked = 'csp_not_checked';

    /**
     * Get translation key
     */
    public function translationKey(): string
    {
        return 'admin/settings/plugins/index.verification.'.$this->value;
    }

    /**
     * Get label
     */
    public function label(): string
    {
        return __($this->translationKey());
    }

    /**
     * Get CSS class (for badge)
     */
    public function badgeClass(): string
    {
        return match ($this) {
            // Signature
            self::SignatureValid => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            self::SignatureUnsigned => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
            self::SignatureInvalid => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            self::SignaturePending => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',

            // Permission
            self::PermissionOk => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            self::PermissionUndefined => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
            self::PermissionMismatch => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',

            // Scan
            self::ScanNotPerformed => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
            self::ScanOutdated => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            self::ScanCompleted => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',

            // CSP
            self::CspReady => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            self::CspCompatible => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
            self::CspInlineRequired => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
            self::CspNotChecked => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
        };
    }

    /**
     * Get icon class
     */
    public function iconClass(): string
    {
        return match ($this) {
            // Signature
            self::SignatureValid => 'fas fa-check-circle',
            self::SignatureUnsigned => 'fas fa-file-signature',
            self::SignatureInvalid => 'fas fa-times-circle',
            self::SignaturePending => 'fas fa-clock',

            // Permission
            self::PermissionOk => 'fas fa-check-circle',
            self::PermissionUndefined => 'fas fa-question-circle',
            self::PermissionMismatch => 'fas fa-code-branch',

            // Scan
            self::ScanNotPerformed => 'fas fa-search',
            self::ScanOutdated => 'fas fa-history',
            self::ScanCompleted => 'fas fa-check',

            // CSP
            self::CspReady => 'fas fa-shield-alt',
            self::CspCompatible => 'fas fa-shield-alt',
            self::CspInlineRequired => 'fas fa-exclamation-triangle',
            self::CspNotChecked => 'fas fa-question',
        };
    }

    /**
     * Whether it is signature status
     */
    public function isSignatureStatus(): bool
    {
        return in_array($this, [
            self::SignatureValid,
            self::SignatureUnsigned,
            self::SignatureInvalid,
            self::SignaturePending,
        ], true);
    }

    /**
     * Whether it is permission status
     */
    public function isPermissionStatus(): bool
    {
        return in_array($this, [
            self::PermissionOk,
            self::PermissionUndefined,
            self::PermissionMismatch,
        ], true);
    }

    /**
     * Whether it is scan status
     */
    public function isScanStatus(): bool
    {
        return in_array($this, [
            self::ScanNotPerformed,
            self::ScanOutdated,
            self::ScanCompleted,
        ], true);
    }

    /**
     * Whether it is CSP status
     */
    public function isCspStatus(): bool
    {
        return in_array($this, [
            self::CspReady,
            self::CspCompatible,
            self::CspInlineRequired,
            self::CspNotChecked,
        ], true);
    }

    /**
     * Whether status has issues
     */
    public function hasIssue(): bool
    {
        return in_array($this, [
            self::SignatureInvalid,
            self::PermissionMismatch,
            self::ScanOutdated,
            self::CspInlineRequired,
        ], true);
    }

    /**
     * Whether it is a critical issue
     */
    public function isCritical(): bool
    {
        return in_array($this, [
            self::SignatureInvalid,
            self::PermissionMismatch,
        ], true);
    }

    /**
     * Whether it can be enabled based on CSP mode
     */
    public function canActivateWithCspMode(string $cspMode): bool
    {
        if (! $this->isCspStatus()) {
            return true;
        }

        return match ($cspMode) {
            'strict' => $this === self::CspReady,
            'standard' => in_array($this, [self::CspReady, self::CspCompatible], true),
            'development', 'disabled' => true,
            default => true,
        };
    }

    /**
     * Get signature status
     */
    public static function signatureStatuses(): array
    {
        return [
            self::SignatureValid,
            self::SignatureUnsigned,
            self::SignatureInvalid,
            self::SignaturePending,
        ];
    }

    /**
     * Get permission status
     */
    public static function permissionStatuses(): array
    {
        return [
            self::PermissionOk,
            self::PermissionUndefined,
            self::PermissionMismatch,
        ];
    }

    /**
     * Get CSP status
     */
    public static function cspStatuses(): array
    {
        return [
            self::CspReady,
            self::CspCompatible,
            self::CspInlineRequired,
            self::CspNotChecked,
        ];
    }
}
