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
    'key_id_mismatch' => 'plugin.json と signature.sig の鍵IDが一致しません。',
    'plugin_file_tampering_detected' => 'プラグインファイルの改ざんを検出しました。',
    'plugin_json_no_files_section' => 'plugin.json に files セクションがありません。',
    'plugin_json_not_found' => 'plugin.json が見つかりません。',
    'plugin_json_parse_failed' => 'plugin.json の解析に失敗しました。',
    'signature_file_not_found' => '署名宣言はありますが、署名ファイルが見つかりません。',
    'signature_file_parse_failed' => '署名ファイルの解析に失敗しました。',
    'signature_mismatch_tampering' => '署名が一致しません。プラグインが改ざんされているか、署名鍵が異なる可能性があります。',
    'signature_missing_required_fields' => '署名ファイルに必須フィールドが不足しています。',
    'signature_verification_error' => '署名検証中にエラーが発生しました: ',
    'verification_suspended_no_key' => 'Retrieve public keyできなかったため検証を保留しました。',
];
