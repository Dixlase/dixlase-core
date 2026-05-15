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
    'heading' => '管理画面設定',
    'description' => '管理画面のURLパスやSSL強制などのアクセス設定を管理します。',
    'admin_panel_settings' => '管理画面設定',
    'admin_url' => '管理画面URL',
    'admin_url_prefix' => 'URLプレフィックス',
    'admin_url_suffix' => 'URLサフィックス',
    'admin_url_help' => 'プレフィックスを選択し、サフィックスを入力して管理画面のURLパスを設定します。<br>サフィックスは4文字以上（半角英小文字と数字のみ）です。<br>注意！: 管理画面URLを変更すると、一旦管理画面からログアウトされます。',
    'force_ssl' => 'SSL強制',
    'force_ssl_help' => 'HTTPSでのアクセスを強制します。SSL証明書が設定されている場合のみ有効にしてください。',
    'settings_updated' => '管理画面設定が更新されました。',
    'admin_url_changed' => '管理画面URLが変更されました。新しいURLでログインしてください。',
];
