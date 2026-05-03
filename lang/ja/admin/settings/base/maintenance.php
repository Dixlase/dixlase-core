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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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
    'heading' => 'メンテナンス設定',
    'description' => 'サイトのメンテナンスモードを有効化し、表示メッセージをカスタマイズできます。',
    'maintenance_settings' => 'メンテナンスモード設定',
    'maintenance_mode' => 'メンテナンスモード',
    'maintenance_mode_help' => 'メンテナンスモードを有効にすると、フロント画面にメンテナンス中のメッセージが表示されます。管理者は常にアクセス可能です。',
    'maintenance_message' => 'メンテナンス中の表示メッセージ',
    'maintenance_message_help' => '※メンテナンスモード有効時にフロント画面で表示されます。',
    'default_message' => '現在メンテナンス中です。しばらくお待ちください。',

    // 解除方式
    'release_method' => '解除方式',
    'manual_release' => '手動解除',
    'manual_release_help' => '管理者が手動でメンテナンスモードをOFFにするまで継続します。',
    'auto_release' => '自動解除',
    'auto_release_help' => '指定した日時に自動的にメンテナンスモードが解除されます。',

    // スケジュール設定
    'schedule_settings' => 'スケジュール設定',
    'start_at' => '開始日時（任意）',
    'start_at_help' => '未来の日時を指定すると、その時刻からメンテナンスモードが開始されます。空欄の場合は即時開始です。',
    'release_at' => '終了日時（自動解除時は必須）',
    'release_at_help' => '自動解除を選択した場合、この日時に自動的にメンテナンスモードが解除されます。',

    // プレビュー
    'preview_button' => 'プレビュー表示',
    'preview_help' => '保存前にメンテナンス画面の表示を確認できます。',

    'settings_updated' => 'メンテナンス設定が更新されました。',
];
