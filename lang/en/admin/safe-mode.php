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
    // モードラベル
    'csp_label' => 'CSP Safe Mode',
    'plugins_label' => 'Plugin Safe Mode',
    'theme_label' => 'Theme Safe Mode',

    // CSPバナー
    'csp_banner_title' => 'CSP Safe Mode is Active',
    'csp_banner_message' => 'Content Security Policy headers are disabled. This poses a security risk. Please disable safe mode after completing your configuration.',
    'csp_go_to_settings' => 'CSP Settings',

    // プラグインバナー
    'plugins_banner_title' => 'Plugin Safe Mode is Active',
    'plugins_banner_message' => 'Plugin routes and assets are disabled. Admin pages provided by plugins are currently inaccessible.',
    'plugins_go_to_settings' => 'Plugin Settings',

    // テーマバナー
    'theme_banner_title' => 'Theme Safe Mode is Active',
    'theme_banner_message' => 'The theme is disabled. Front-end pages are displayed with a minimal fallback layout.',
    'theme_go_to_settings' => 'Theme Settings',

    // テーマセーフモード（フロント表示用）
    'theme_safe_mode_label' => 'Safe Mode',
    'theme_safe_mode_title' => 'Theme Safe Mode is Active',
    'theme_safe_mode_front_message' => 'The current theme has been temporarily disabled. Pages are displayed with a minimal layout. To restore the theme, disable safe mode from the admin panel.',

    // アクション
    'disable' => 'Disable',
    'disable_all' => 'Disable All',

    // フラッシュメッセージ
    'disabled' => ':mode has been disabled.',
    'all_disabled' => 'All safe modes have been disabled.',
    'invalid_mode' => 'Invalid safe mode specified.',

    // プラグインルートブロック
    'plugins_route_blocked' => 'Plugin pages are disabled while Plugin Safe Mode is active.',
];
