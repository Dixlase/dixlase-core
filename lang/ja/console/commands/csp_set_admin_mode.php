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
    'admin_csp_mode_change_failed' => '❌ 管理画面CSPモードの変更に失敗しました: ',
    'admin_csp_mode_changed' => '✅ 管理画面のCSPモードを :mode に変更しました',
    'admin_csp_mode_set_to_same_success' => '✅ 管理画面のCSPモードをフロントエンドと同じに設定しました',
    'admin_panel_mode_display' => '  - 管理画面: :mode',
    'command_csp_set_admin_description' => '管理画面専用のCSPモードを設定します（sameでフロントと同じモードを使用）',
    'command_csp_set_admin_signature' => 'dixlase:csp:set-admin {mode? : CSPモード (development/standard/strict/same)}',
    'current_settings' => '📊 現在の設定:',
    'frontend_mode_display' => '  - フロントエンド: :frontModeName',
    'invalid_mode' => '❌ 無効なモード: :mode',
    'log_admin_csp_mode_changed' => '📝 ログ: 管理画面CSPモードが変更されました - ',
    'log_admin_csp_mode_changed_same' => '📝 ログ: 管理画面CSPモードが変更されました（フロントと同じ） - ',
    'select_admin_csp_mode' => '管理画面のCSPモードを選択してください',
    'valid_modes' => '有効なモード: ',
];
