<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
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
    'subject' => '[Security Alert] Admin Login Lockout Occurred',
    'title' => 'Admin Login Lockout Notification',
    'message' => 'An admin login lockout has occurred in the system. This may indicate unauthorized login attempts.',
    'details' => 'Lockout Details',
    'identifier' => 'Email Address',
    'ip_address' => 'IP Address',
    'user_agent' => 'User Agent',
    'timestamp' => 'Occurrence Time',
    'settings' => 'Lockout Settings',
    'max_attempts' => 'Maximum Attempts',
    'time_window' => 'Time Window',
    'lockout_duration' => 'Lockout Duration',
    'times' => ' times',
    'minutes' => ' minutes',
    'action_required' => 'For security reasons, please review this login lockout and take appropriate action as necessary.',
    'thanks' => 'Thank you for your attention',
];
