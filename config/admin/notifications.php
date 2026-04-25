<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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
    // ログイン時のメール通知設定
    'global_login_notification_mail_mode' => [0, 1, 2, 3], // 0: 無効, 1: 異なる端末/IP時のみ有効, 2: 常に有効, 3: メンバーのプロフィール設定を反映
    'members_login_notification_mail_mode' => [0, 1, 2], // 0: 無効, 1: 異なる端末/IP時のみ有効, 2: 常に有効

    // 二段階認証の設定
    'global_two_fa_mode' => [0, 1, 2, 3], // 0: 無効, 1: 異なるデバイス・IP時のみ, 2: 常に有効, 3: メンバーのプロフィール設定に従う
    'members_two_factor_mode' => [0, 1, 2], // 0: 無効, 1: 異なるデバイス・IP時のみ, 2: 常に有効
];
