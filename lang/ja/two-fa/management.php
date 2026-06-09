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

return [
    // デバイス管理
    'devices' => [
        'passkey_devices' => 'Passkeyデバイス',
        'no_devices' => 'デバイスが登録されていません',
        'add_device' => 'デバイスを追加',
        'delete_device' => 'デバイスを削除',
        'delete_all' => '全て削除',
        'registered_at' => '登録日時',
        'last_used' => '最終使用',
    ],

    // 回復コード管理
    'recovery_codes' => [
        'title' => '回復コード',
        'generate' => '回復コードを生成',
        'regenerate' => '回復コードを再生成',
        'not_generated' => '回復コードはまだ生成されていません。',
        'remaining' => '残り :count 個の回復コードがあります。',
        'warning' => 'これらのコードは一度しか表示されません。<br>ダウンロード、コピー、スクリーンショット、写真撮影、印刷などの方法で安全な場所に保管してください。<br>また、コードは他人と共有しないでください。',
        'download' => 'ダウンロード',
        'copy' => 'コピー',
        'confirm_saved' => '回復コードを安全な場所に保管しました',
        'auto_generated_title' => '回復コードが自動生成されました',
        'auto_generated_message' => '二段階認証の初回クリア後、緊急時のために回復コードが自動生成されました。これらのコードは今後表示されませんので、必ず保管してください。',
    ],
];
