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
 */

return [
    'heading' => 'メンバー編集',
    'confirm_title' => '更新確認',
    'confirm_message' => 'この内容でメンバー情報を更新しますか？',
    'modals' => [
        'force_logout' => [
            'title' => '強制ログアウト確認',
            'message' => ':name を強制的にログアウトしますか？',
            'confirm' => '強制ログアウト実行',
        ],
        'unlock_lockout' => [
            'title' => 'ロックアウト解除確認',
            'message' => ':name のログインおよび二段階認証のロックアウトを解除しますか？',
            'confirm' => 'ロックアウト解除',
        ],
        'delete' => [
            'title' => 'メンバー削除確認',
            'message' => ':name を完全に削除しますか？',
            'warning' => 'この操作は取り消せません。',
        ],
    ],
    'messages' => [
        'updated' => 'メンバー情報を更新しました。',
        'updated_with_verification_email' => 'メンバー情報を更新しました。認証メールを送信しました。',
        'updated_but_email_failed' => 'メンバー情報を更新しましたが、認証メールの送信に失敗しました。',
        'verification_email_sent' => '認証メールを送信しました。',
        'verification_email_failed' => '認証メールの送信に失敗しました。',
        'unlock_lockout_success' => 'ロックアウトを解除しました。',
        'force_logout_success' => 'メンバーを強制ログアウトしました。',
        'deleted' => 'メンバーアカウントを削除しました。',
    ],
];
