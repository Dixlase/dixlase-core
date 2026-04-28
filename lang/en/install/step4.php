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
    'mail_title' => 'Mail Server Settings',
    'mail_header' => 'Mail Server Settings (Optional)',
    'mail_description' => 'Enter the mail server information that the application will use to send emails.<br>You can skip this step and configure it after installation.',

    // Mail Server Settings Related
    'mail_server_settings' => 'Mail Server Settings',
    'mail_connection_test' => 'Mail Connection Test',

    // Mail Test Features
    'mail_test' => [
        'title' => 'Mail Test',
        'description' => 'You can test mail server connection and mail sending.',
        'description_admin_email' => 'Test email will be sent to the admin email address entered in basic settings.',
    ],
    'mail_test_description' => 'You can test mail server connection and mail sending.',
    'mail_test_description_admin_email' => 'Test email will be sent to the admin email address entered in basic settings.',

    'mail_test_advanced' => [
        'three_stage_test_incomplete' => '3-stage mail test incomplete',
        'three_stage_test_complete' => '3-stage mail test complete',
        'connection_test' => 'Server Connection Test',
        'send_test' => 'Mail Send Test',
        'receive_test' => 'Mail Receipt Verification',
    ],
];
