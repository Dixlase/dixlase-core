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
    'heading' => '有効なセッション',
    'section_title' => '有効なセッション',
    'description' => 'ログイン中で、セッションの有効期間内に使われた管理画面のセッションです。セッションを終了すると、その端末がログアウトし、そのメンバーの「ログイン状態を保持する」によるログインがすべての端末で効かなくなります。ほかのセッションはログインしたままです。',
    'unavailable' => 'このサイトではセッションがデータベースに保存されていないため、セッションを一覧できません。',
    'empty' => '有効なセッションはありません。',
    'view_all' => '有効なセッション',
    'caption' => '有効なセッション',
    'columns' => [
        'member' => 'メンバー',
        'device' => '端末',
        'ip_address' => 'IP アドレス',
        'started_at' => '開始',
        'last_activity' => '最終利用',
        'actions' => '操作',
    ],
    'device' => ':os の :browser',
    'unknown' => '不明',
    'current' => 'このセッション',
    'revoke' => 'セッションを終了',
    'revoked' => 'セッションを終了しました。',
    'not_found' => 'セッションが見つかりません。すでに終了している可能性があります。',
    'cannot_revoke_current' => '使用中のセッションはここでは終了できません。ログアウトしてください。',
];
