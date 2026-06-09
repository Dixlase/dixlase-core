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
    // デフォルトメッセージ
    'default_message' => 'セキュリティ上の理由により、システムは現在ロックダウン中です。',

    // タイプ
    'types' => [
        'full' => '完全ロックダウン',
        'admin' => '管理画面ロックダウン',
        'api' => 'APIロックダウン',
        'login' => 'ログインロックダウン',
    ],

    // アクション
    'actions' => [
        'activated' => 'ロックダウン発動',
        'deactivated' => 'ロックダウン解除',
        'extended' => 'ロックダウン延長',
        'modified' => 'ロックダウン変更',
        'auto_released' => '自動解除',
    ],

    // エラーページ
    'error_title' => 'システムロックダウン中',
    'error_message' => 'セキュリティ上の理由により、現在システムへのアクセスが制限されています。',
    'contact_admin' => '管理者にお問い合わせください。',
];
