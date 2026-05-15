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
    'title' => '二段階認証',

    // 二段階認証方法
    'method' => [
        'email' => 'メール認証',
        'passkey' => 'Passkey認証',
    ],

    // セキュリティレベル
    'security' => [
        'level' => [
            'very_high' => '非常に高い',
            'high' => '高い',
            'medium' => '中程度',
            'low' => '低い',
        ],
        'description' => [
            'passkey' => '生体認証またはセキュリティキーを使用する最も安全な方法です。デバイスに保存された認証情報を使用するため、フィッシング攻撃に強く、安全にログインできます。',
            'email' => 'メールアドレスに送信される認証コードを使用します。有効期限や使用回数制限により保護されていますが、メールアカウントのセキュリティに依存します。より高いセキュリティが必要な場合はPasskeyの使用を推奨します。',
            'recovery_code' => '緊急時のバックアップ手段です。Passkeyやメール認証が使用できない場合に使用します。回復コードは一度しか使用できず、使用後は無効になります。安全な場所に保管してください。',
        ],
        'recommended' => '推奨',
        'backup' => 'バックアップ',
    ],

    // 認証方法切り替え
    'switch_method_prompt' => '別の認証方法に切り替える',
    'switch_to_passkey' => 'Passkey認証に切り替える',
    'switch_to_email' => 'メール認証に切り替える',

    // 共通
    'back_to_login' => 'ログイン画面に戻る',
    'alternative_methods_prompt' => '別の認証方法を使用しますか？',
    'awaiting_approval' => '承認待機中...',

    // ロックアウト
    'lockout' => [
        'message' => '二段階認証の試行回数が上限に達しました。:minutes分後に再度お試しください。',
        'locked' => '二段階認証の試行回数が上限に達しました。:minutes分間ロックされます。',
    ],

    // 2FAモード
    'mode' => [
        'disabled' => '無効',
        'enabled' => '有効',
        'use_profile' => 'プロフィール設定に従う',
        'always' => '常に有効',
    ],
];
