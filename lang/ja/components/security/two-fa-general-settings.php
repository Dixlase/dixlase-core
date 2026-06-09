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
    'mode_label' => '二段階認証モード',
    'help' => '二段階認証を有効にすると、ログイン時に追加の認証が必要になります。',
    'method_label' => '二段階認証方法',
    'email_always_enabled' => 'メール認証は常に有効です',
    'passkey' => 'パスキー',
    'passkey_disabled_globally' => '全体設定で無効になっています',
    'method_note' => '二段階認証方法は全体設定で管理されています。',
    'change_in_global_settings' => '全体設定で変更',
    'default_method' => 'デフォルトの認証方法',
    'passkey_disabled_default_email_only' => 'パスキーが無効の場合、メール認証のみ使用できます。',
    'default_method_help' => 'ログイン時に最初に使用する認証方法を選択します。',
    'global_setting_fixed' => '全体設定により固定されています',
    'authentication_mode' => [
        'disabled' => '無効',
        'different_device' => '異なるデバイス・IPでのログイン時',
        'always' => '常に有効',
        'use_profile_setting' => 'プロフィール設定に従う',
    ],
    'options' => [
        'disabled' => '無効',
        'different_device' => '異なるデバイス・IPでのログイン時',
        'always' => '常に有効',
        'use_profile_setting' => 'プロフィール設定に従う',
    ],
    'passkey_mode' => [
        'label' => 'パスキー設定',
        'help' => [
            'profile_editable' => 'パスキー認証の利用可否を設定できます',
            'profile_forced_disabled' => '全体設定により、パスキー認証は無効に設定されています',
            'profile_forced_enabled' => '全体設定により、パスキー認証は有効に設定されています',
        ],
        'options' => [
            'enabled' => '有効',
        ],
    ],
];
