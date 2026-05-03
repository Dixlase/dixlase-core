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

    'warning_minimal' => '⚠️ Warning: Minimal mode disables all security features.',
    'minimal_description' => 'Features disabled: CAPTCHA, IP restrictions, Lockdown, Login lockout',
    'confirm_minimal' => 'Switch to minimal configuration?',
    'cancelled' => 'Operation cancelled.',
    'minimal_success' => '✅ Security settings reset to minimal configuration.',
    'security_notice' => '⚠️ All security features are disabled. Make sure to reconfigure after recovery.',
    'restore_hint' => 'To restore from backup: check JSON files in storage/app/',
    'warning_category' => '⚠️ Warning: Resetting :category category settings.',
    'warning_full' => '⚠️ Warning: Resetting all security settings to defaults.',
    'confirm_full' => 'Reset security settings?',
    'full_success' => '✅ :count security settings reset to defaults.',
    'status_title' => '【Security Settings Status】',
    'setting' => 'Setting',
    'value' => 'Value',
    'exported' => '✅ Settings exported to: :path',
    'backup_created' => '📁 Pre-reset backup created: :path',
    'cache_cleared' => '🗑️ Security-related caches cleared.',
    'reason_prompt' => 'Enter the reason for reset',
    'reason_required' => 'Reason is required.',
    'invalid_action' => 'Invalid action: :action',
    'valid_actions' => 'Valid actions:',
    'action_minimal' => 'Minimal configuration (disable all)',
    'action_full' => 'Reset to defaults',
    'action_status' => 'Show current settings',
    'action_export' => 'Export settings',
];
