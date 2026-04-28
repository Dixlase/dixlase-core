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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

    /*
    |--------------------------------------------------------------------------
    | API Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for API responses and error messages.
    |
    */

    'signature' => [
        'errors' => [
            'missing_headers' => '必要な署名ヘッダーがありません',
            'timestamp_expired' => 'タイムスタンプが有効期限切れです',
            'invalid_api_key' => 'APIキーが無効です',
            'unsupported_version' => 'サポートされていない署名バージョンです',
            'invalid_signature' => '署名の検証に失敗しました',
            'revoked_key' => 'APIキーは無効化されています',
        ],
    ],

    'responses' => [
        'success' => '成功',
        'error' => 'エラーが発生しました',
        'not_found' => 'リソースが見つかりません',
        'unauthorized' => '認証が必要です',
        'forbidden' => 'アクセスが拒否されました',
        'validation_error' => '入力内容に誤りがあります',
        'server_error' => 'サーバーエラーが発生しました',
    ],

];
