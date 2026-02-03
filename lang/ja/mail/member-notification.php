<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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
    // 管理者向け通知
    'admin_notification' => [
        'member_verified' => [
            'subject' => 'メンバーアカウントの認証完了通知',
            'greeting' => 'システム管理者様',
            'title' => 'メンバーアカウントの認証完了通知',
            'message' => 'メンバーアカウントの認証が完了しました。',
            'member_info' => '【メンバー情報】',
            'name' => '名前',
            'email' => 'メールアドレス',
            'verified_at' => '認証完了日時',
            'login_available' => 'このメンバーはログイン可能な状態になりました。',
            'urls' => '【URL情報】',
            'front_url' => 'フロントページURL',
            'admin_url' => '管理画面URL',
            'notification_time' => '通知日時',
            'regards' => 'よろしくお願いします。',
        ],
    ],
];
