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

namespace App\Enums;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * プラグイン・テーマの検証状態
 *
 * 署名と権限定義の検証状態を表します。
 */
enum PluginVerificationStatus: string
{
    // ========================================
    // 署名ステータス
    // ========================================

    /**
     * 署名OK - 署名が有効で検証済み
     */
    case SignatureValid = 'signature_valid';

    /**
     * 未署名 - 署名がない
     */
    case SignatureUnsigned = 'signature_unsigned';

    /**
     * 署名不一致 - 署名が無効（改ざんの可能性）
     */
    case SignatureInvalid = 'signature_invalid';

    /**
     * 署名検証待ち - 署名はあるが未検証
     */
    case SignaturePending = 'signature_pending';

    // ========================================
    // 権限定義ステータス
    // ========================================

    /**
     * 権限定義OK - 宣言と実態が一致
     */
    case PermissionOk = 'permission_ok';

    /**
     * 権限未定義 - plugin.jsonにpermissionsがない
     */
    case PermissionUndefined = 'permission_undefined';

    /**
     * 権限不一致 - 宣言と実態が不一致
     */
    case PermissionMismatch = 'permission_mismatch';

    // ========================================
    // スキャンステータス
    // ========================================

    /**
     * スキャン未実行
     */
    case ScanNotPerformed = 'scan_not_performed';

    /**
     * スキャン期限切れ（ルール更新後など）
     */
    case ScanOutdated = 'scan_outdated';

    /**
     * スキャン完了
     */
    case ScanCompleted = 'scan_completed';

    // ========================================
    // CSP適合性ステータス
    // ========================================

    /**
     * CSP Ready - CSP完全対応
     */
    case CspReady = 'csp_ready';

    /**
     * CSP互換 - nonce付きで動作
     */
    case CspCompatible = 'csp_compatible';

    /**
     * インラインJS必須 - CSP厳格モードで動作不可
     */
    case CspInlineRequired = 'csp_inline_required';

    /**
     * CSP未検証
     */
    case CspNotChecked = 'csp_not_checked';

    /**
     * 翻訳キーを取得
     */
    public function translationKey(): string
    {
        return 'admin/settings/plugins.verification.'.$this->value;
    }

    /**
     * ラベルを取得
     */
    public function label(): string
    {
        return __($this->translationKey());
    }

    /**
     * CSSクラスを取得（バッジ用）
     */
    public function badgeClass(): string
    {
        return match ($this) {
            // 署名
            self::SignatureValid => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            self::SignatureUnsigned => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
            self::SignatureInvalid => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
            self::SignaturePending => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',

            // 権限
            self::PermissionOk => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            self::PermissionUndefined => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
            self::PermissionMismatch => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',

            // スキャン
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
     * アイコンクラスを取得
     */
    public function iconClass(): string
    {
        return match ($this) {
            // 署名
            self::SignatureValid => 'fas fa-check-circle',
            self::SignatureUnsigned => 'fas fa-file-signature',
            self::SignatureInvalid => 'fas fa-times-circle',
            self::SignaturePending => 'fas fa-clock',

            // 権限
            self::PermissionOk => 'fas fa-check-circle',
            self::PermissionUndefined => 'fas fa-question-circle',
            self::PermissionMismatch => 'fas fa-code-branch',

            // スキャン
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
     * 署名ステータスかどうか
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
     * 権限ステータスかどうか
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
     * スキャンステータスかどうか
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
     * CSPステータスかどうか
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
     * 問題があるステータスかどうか
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
     * 致命的な問題かどうか
     */
    public function isCritical(): bool
    {
        return in_array($this, [
            self::SignatureInvalid,
            self::PermissionMismatch,
        ], true);
    }

    /**
     * CSPモードに基づいて有効化可能かどうか
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
     * 署名ステータスを取得
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
     * 権限ステータスを取得
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
     * CSPステータスを取得
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
