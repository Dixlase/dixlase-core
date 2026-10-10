<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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
    'checking' => ':name を拡張機能のセキュリティ設定で確認しています...',
    'blocked' => ':name は拡張機能のセキュリティ設定(健全性のスコアまたは署名)で拒否されるため、:action しませんでした。管理画面でも拒否されます。',
    'blocked_hint' => '`php artisan dls::kind:audit :slug` で指摘を確認してください。拒否は --force でも解除できません。',
    'confirmation_required' => ':name には管理画面で確認を求める健全性の問題(:level)があるため、:action しませんでした。',
    'confirmation_hint' => '`php artisan dls::kind:audit :slug` で内容を確認し、それでも進めるには --force を付けて再実行してください。',
    'forced' => '警告: --force が指定されたため、健全性の問題(:level)がある :name をそのまま進めます。',
    'level' => [
        'warning' => '軽微な問題',
        'ack' => '重要な問題',
    ],
    'action' => [
        'installed' => 'インストール',
        'enabled' => '有効化',
        'switched' => '切り替え',
    ],
];
