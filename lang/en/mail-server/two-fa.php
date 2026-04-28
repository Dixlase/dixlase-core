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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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
    // Common Messages
    'security_notice' => 'If you do not recognize this login attempt, a third party may have tried to log in. There is a risk of unauthorized access, so please change your password immediately or contact your system administrator.',
    'regards' => 'Best regards,',
    'details_title' => 'Login Attempt Details',
    'ip_address' => 'IP Address',
    'user_agent' => 'Browser/Device',
    'timestamp' => 'Date & Time',

    // Email Authentication
    'email' => [
        'subject' => '[:app_name] Two-Factor Authentication Code',
        'greeting' => 'Hello!',
        'message' => 'Here is your two-factor authentication code for login.',
        'instructions' => 'Please enter this code on the login screen. The code is valid for 10 minutes.',
    ],
];
