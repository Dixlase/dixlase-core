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
    'author_id_not_defined' => 'plugin.json に author_id が定義されていません。',
    'authority_key_id_not_defined' => 'plugin.json に authority_key_id が定義されていません。',
    'csp_violation_detected' => 'CSP違反が検出されました（:cspModeモード）。',
    'dangerous_api_detected' => '危険なAPIが検出されました: :permission',
    'direct_upload_to_public_dir' => '公開ディレクトリへ直接アップロードします。',
    'inline_css_required_strict_mode' => 'インラインCSSが必要です。厳格モードでは動作しない可能性があります。',
    'inline_js_required_strict_mode' => 'インラインJavaScriptが必要です。厳格モードでは動作しません。',
    'no_signature_recommend_signing' => '署名がありません。配布時は署名を推奨します。',
    'permissions_not_defined_in_json' => 'plugin.json に permissions セクションが定義されていません。',
    'permissions_section_undefined' => 'permissions セクションが未定義です。',
    'public_upload_in_dedicated_dir' => '専用ディレクトリ内で公開アップロードを使用します。',
    'scan_outdated_rescan_recommended' => 'スキャンが古くなっています（:daysSinceScan日前）。再スキャンを推奨します。',
    'signature_invalid_tampering' => '署名が無効です。改ざんの可能性があります。',
    'signature_verification_error' => '署名検証中にエラーが発生しました。',
    'signature_verification_incomplete_keyserver' => '署名の検証が完了していません（公開鍵サーバー未接続）。',
    'signing_key_not_trusted' => '署名鍵が信頼済みとして登録されていません。',
    'signing_key_revoked' => '署名に使用された鍵が失効しています。',
    'undeclared_permission_usage' => '未宣言の権限使用: :permission',
    'unused_permission_declaration' => '未使用の権限宣言: ',
    'uses_bulk_email_permission' => 'メールの一括送信権限を使用します。',
    'uses_member_deletion_permission' => 'メンバーの削除権限を使用します。',
];
