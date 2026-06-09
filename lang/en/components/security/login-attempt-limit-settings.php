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
    'enabled' => 'Login Attempt Limit',
    'enabled_help' => 'Temporarily lock the account after a certain number of failed login attempts.',
    'max_attempts' => 'Maximum Attempts (Identifier-based)',
    'max_attempts_help' => 'Set the maximum number of login attempts before lockout.',
    'max_attempts_ip' => 'Maximum Attempts (IP-based)',
    'max_attempts_ip_help' => 'Set the maximum number of login attempts before lockout based on IP address.',
    'time_window' => 'Time Window (minutes)',
    'time_window_help' => 'Set the time window in minutes for counting attempts.',
    'lockout_duration' => 'Lockout Duration (minutes)',
    'lockout_duration_help' => 'Set the duration in minutes that the account will be locked.',
    'notification_enabled' => 'Lockout Notification',
    'notification_help' => 'Notify administrators when an account is locked.',
];
