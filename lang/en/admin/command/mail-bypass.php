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
    'invalid_scope' => 'Invalid scope: :scope (use two_fa, password_reset, or all)',
    'reason_prompt' => 'Enter the reason for enabling bypass',
    'reason_required' => 'Reason is required.',
    'warning' => '⚠️ Warning: Mail bypass poses a security risk.',
    'confirm_details' => 'Settings: :minutes minutes, scope: :scope, reason: :reason',
    'confirm_enable' => 'Enable mail bypass?',
    'cancelled' => 'Operation cancelled.',
    'enabled' => '✅ Mail bypass enabled (:minutes minutes, until :expires_at)',
    'enable_failed' => 'Failed to enable mail bypass.',
    'security_notice' => '⚠️ Mail-dependent features are temporarily disabled. Make sure to disable after recovery.',
    'not_active' => 'Mail bypass is not currently active.',
    'disabled' => '✅ Mail bypass has been disabled.',
    'status_title' => '【Mail Bypass Status】',
    'status_active' => '⚠️ Bypass is ACTIVE',
    'status_inactive' => '✅ Bypass is inactive (normal operation)',
    'field' => 'Field',
    'value' => 'Value',
    'scope' => 'Scope',
    'reason' => 'Reason',
    'expires_at' => 'Expires at',
    'remaining' => 'Remaining',
    'minutes' => 'minutes',
    'enabled_at' => 'Enabled at',
    'affected_features' => 'Affected features:',
    'feature_two_fa' => 'Two-factor authentication (email)',
    'feature_password_reset' => 'Password reset',
    'invalid_action' => 'Invalid action: :action',
    'valid_actions' => 'Valid actions:',
    'action_enable' => 'Enable bypass',
    'action_disable' => 'Disable bypass',
    'action_status' => 'Show current status',
];
