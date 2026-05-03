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
    // モードラベル
    'csp_label' => 'CSPセーフモード',
    'plugins_label' => 'プラグインセーフモード',
    'theme_label' => 'テーマセーフモード',

    // CSPバナー
    'csp_banner_title' => 'CSPセーフモードが有効です',
    'csp_banner_message' => 'CSPヘッダーが無効化されています。セキュリティリスクがあるため、設定完了後は必ずセーフモードを解除してください。',
    'csp_go_to_settings' => 'CSP設定へ',

    // プラグインバナー
    'plugins_banner_title' => 'プラグインセーフモードが有効です',
    'plugins_banner_message' => 'プラグインのルートとアセットが無効化されています。プラグインが提供する管理ページは現在アクセスできません。',
    'plugins_go_to_settings' => 'プラグイン設定へ',

    // テーマバナー
    'theme_banner_title' => 'テーマセーフモードが有効です',
    'theme_banner_message' => 'テーマが無効化されています。フロントページは最小限のフォールバックレイアウトで表示されます。',
    'theme_go_to_settings' => 'テーマ設定へ',

    // テーマセーフモード（フロント表示用）
    'theme_safe_mode_label' => 'セーフモード',
    'theme_safe_mode_title' => 'テーマセーフモードが有効です',
    'theme_safe_mode_front_message' => '現在のテーマが一時的に無効化されています。最小限のレイアウトでページが表示されています。テーマを復元するには、管理画面からセーフモードを解除してください。',

    // アクション
    'disable' => '解除',
    'disable_all' => 'すべて解除',

    // フラッシュメッセージ
    'disabled' => ':modeを解除しました。',
    'all_disabled' => 'すべてのセーフモードを解除しました。',
    'invalid_mode' => '無効なセーフモードが指定されました。',

    // プラグインルートブロック
    'plugins_route_blocked' => 'プラグインセーフモードが有効なため、プラグインページは無効化されています。',
];
