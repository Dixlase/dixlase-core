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

return [
    'audit_scan_not_executed' => '監査スキャンが未実行です。',
    'audit_scan_not_run' => '監査スキャンが実行されていません。',
    'csp_violation_detected' => 'CSP違反が検出されました（:cspModeモード）。',
    'dangerous_api_detected' => '危険なAPIが検出されました: :permission',
    'inline_css_strict_mode_warning' => 'インラインCSSが必要です。厳格モードでは動作しない可能性があります。',
    'inline_js_strict_mode_error' => 'インラインJavaScriptが必要です。厳格モードでは動作しません。',
    'invalid_signature_tampering' => '署名が無効です。改ざんの可能性があります。',
    'no_signature_recommend_signing' => '署名がありません。配布時は署名を推奨します。',
    'permissions_section_undefined' => 'permissions セクションが未定義です。',
    'scan_outdated_rescan_recommended' => 'スキャンが古くなっています（:daysSinceScan日前）。再スキャンを推奨します。',
    'undeclared_permission_used' => '未宣言の権限使用: :permission',
    'unused_permission_declaration' => '未使用の権限宣言: ',
    'api_version_missing' => 'theme.json に requires.dixlase_api が宣言されていません。',
    'api_version_incompatible' => 'テーマは Extension API :declared を要求していますが、コアは :supported に対応しています。',
    'api_constraint_malformed' => 'requires.dixlase_api が有効な semver 制約ではありません。',
];
