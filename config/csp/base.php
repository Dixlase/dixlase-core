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

return [
    'enabled' => env('CSP_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | CSP Mode
    |--------------------------------------------------------------------------
    |
    | Specifies the CSP operation mode.
    |
    | - 'development': Development mode
    |   - Report-Only (logs only, does not block)
    |   - Inline JS/CSS allowed (unsafe-inline)
    |   - eval allowed (unsafe-eval)
    |   - Only denied domains can be blocked
    |   - Plugin compatibility: Maximum
    |
    | - 'standard': Standard mode (recommended for production)
    |   - CSP enforced (blocking)
    |   - Inline executable code: Only allowed via helper (with nonce)
    |   - onclick and other attribute events: Warning (allowed during transition period)
    |   - unsafe-eval prohibited
    |   - strict-dynamic recommended (optional)
    |   - Plugin compatibility: High
    |
    | ※ 'strict' (strict mode) is not implemented in the initial version
    |
    | Actual settings are loaded from the database (SecuritySetting).
    |
    */
    'mode' => env('CSP_MODE', 'development'),

    /*
    |--------------------------------------------------------------------------
    | Admin CSP Mode (dedicated CSP mode for admin panel)
    |--------------------------------------------------------------------------
    |
    | Different CSP modes can be used for admin panel and frontend.
    |
    | - null: Use same mode as frontend (default)
    | - 'development' / 'standard': Admin panel specific mode
    |
    | Recommended settings:
    | - Frontend: standard (recommended for production)
    | - Admin panel: standard (balance of usability and security)
    |
    */
    'admin_mode' => env('CSP_ADMIN_MODE', null),

    /*
    |--------------------------------------------------------------------------
    | CSP Mode Definitions
    |--------------------------------------------------------------------------
    |
    | Detailed settings for each mode
    |
    */
    'modes' => [
        // Development mode: Maximum compatibility, log violations with Report-Only
        'development' => [
            'header' => 'Content-Security-Policy-Report-Only',
            'allow_inline_scripts' => false, // false to log violations (works because Report-Only)
            'allow_inline_styles' => true,
            'allow_eval' => true,
            'allow_unsafe_inline' => false,  // false to log violations (works because Report-Only)
            'require_nonce' => true,         // Recommend scripts with nonce
            'block_inline_plugins' => false,
            'enforce_deny_domains' => false, // Denied domains are warning only
            'strict_dynamic' => false,       // false for Vite compatibility
            'block_script_attr' => false,    // Works because Report-Only
            'description' => 'For theme/plugin development. Everything works but violations are logged.',
            'description_en' => 'For theme/plugin development. Everything works but violations are logged.',
        ],

        // Standard mode: Recommended for production, only allow inline with nonce
        'standard' => [
            'header' => 'Content-Security-Policy',
            'allow_inline_scripts' => false, // Prohibit unsafe-inline
            'allow_inline_styles' => true,   // Allow for Alpine.js inline styles
            'allow_eval' => true,            // Allow because Alpine.js requires it
            'allow_unsafe_inline' => false,
            'require_nonce' => true,         // Require nonce via helper
            'allow_nonce_inline_execution' => true, // Allow executed code with nonce
            'block_inline_plugins' => true,
            'enforce_deny_domains' => true,  // Force block denied domains
            'strict_dynamic' => false,       // Disable for testing (blocks dynamic scripts)
            'warn_onclick' => true,          // Warn (but don't block) onclick etc.
            'block_script_attr' => true,     // Prohibit unsafe-inline in script-src-attr
            'description' => 'Recommended for production. Inline via helper is allowed.',
            'description_en' => 'Recommended for production. Inline via helpers allowed.',
        ],

        /*
        // 厳格モード: 最大セキュリティ、外部JSのみ（初期バージョンでは未実装）
        'strict' => [
            'header' => 'Content-Security-Policy',
            'allow_inline_scripts' => false,
            'allow_inline_styles' => false,
            'allow_eval' => false,
            'allow_unsafe_inline' => false,
            'require_nonce' => false,        // nonceも使用しない（外部JSのみ）
            'allow_nonce_inline_execution' => false, // nonce付きでも実行コード禁止
            'allow_json_script' => true,     // type="application/json"は許可
            'allow_data_attributes' => true, // data-*属性は許可
            'block_inline_plugins' => true,  // requires_inline_js: trueを拒否
            'enforce_deny_domains' => true,
            'strict_dynamic' => true,        // 推奨ON
            'block_onclick' => true,         // onclick等を完全ブロック
            'require_bootloader' => true,    // dixlase-boot.js必須
            'description' => 'Maximum security. Only CSP Ready plugins work.',
            'description_en' => 'Maximum security. Only CSP Ready plugins work.',
        ],
        */
    ],

    /*
    |--------------------------------------------------------------------------
    | Report URI
    |--------------------------------------------------------------------------
    |
    | Path to the endpoint for sending CSP violation reports.
    | This path is excluded from CSP middleware.
    |
    */
    'report_uri' => '/csp-report',

    /*
    |--------------------------------------------------------------------------
    | Nonce Length
    |--------------------------------------------------------------------------
    |
    | Length of generated nonce (in bytes).
    | Recommended: 16 bytes or more (approximately 22 characters after Base64 encoding)
    |
    */
    'nonce_length' => 16, ];
