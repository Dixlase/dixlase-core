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
    'admin_csp_mode_change_failed' => '❌ Failed to change admin panel CSP mode:',
    'admin_csp_mode_changed' => '✅ Changed admin panel CSP mode to :mode',
    'admin_csp_mode_set_to_same_success' => '✅ Admin panel CSP mode set to same as frontend',
    'admin_panel_mode_display' => '  - Admin panel: :mode',
    'command_csp_set_admin_description' => 'Set CSP mode for admin panel (use same to apply frontend mode)',
    'command_csp_set_admin_signature' => 'dixlase:csp:set-admin {mode? : CSP mode (development/standard/strict/same)}',
    'current_settings' => '📊 Current settings:',
    'frontend_mode_display' => '  - Frontend: :frontModeName',
    'invalid_mode' => '❌ Invalid mode: :mode',
    'log_admin_csp_mode_changed' => '📝 Log: Admin panel CSP mode changed -',
    'log_admin_csp_mode_changed_same' => '📝 Log: Admin panel CSP mode changed (same as frontend) -',
    'select_admin_csp_mode' => 'Please select CSP mode for admin panel',
    'valid_modes' => 'Valid modes:',
];
