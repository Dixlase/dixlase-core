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
    'heading' => 'Active Sessions',
    'section_title' => 'Active sessions',
    'description' => 'Admin sessions that are logged in and have been used within the session lifetime. Ending a session logs that device out and stops the member\'s "Remember me" logins on every device; their other sessions stay logged in.',
    'unavailable' => 'Sessions cannot be listed because they are not stored in the database on this site.',
    'empty' => 'No active sessions.',
    'view_all' => 'Active sessions',
    'caption' => 'Active sessions',
    'columns' => [
        'member' => 'Member',
        'device' => 'Device',
        'ip_address' => 'IP address',
        'started_at' => 'Started',
        'last_activity' => 'Last active',
        'actions' => 'Actions',
    ],
    'device' => ':browser on :os',
    'unknown' => 'Unknown',
    'current' => 'This session',
    'revoke' => 'End session',
    'revoked' => 'The session was ended.',
    'not_found' => 'The session was not found. It may already have ended.',
    'cannot_revoke_current' => 'The session you are using cannot be ended here. Log out instead.',
];
