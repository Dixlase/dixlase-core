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
    // Default message
    'default_message' => 'The system is currently in lockdown mode for security reasons.',

    // Types
    'types' => [
        'full' => 'Full Lockdown',
        'admin' => 'Admin Panel Lockdown',
        'api' => 'API Lockdown',
        'login' => 'Login Lockdown',
    ],

    // Actions
    'actions' => [
        'activated' => 'Lockdown Activated',
        'deactivated' => 'Lockdown Deactivated',
        'extended' => 'Lockdown Extended',
        'modified' => 'Lockdown Modified',
        'auto_released' => 'Auto Released',
    ],

    // Error page
    'error_title' => 'System Lockdown',
    'error_message' => 'Access to the system is currently restricted for security reasons.',
    'contact_admin' => 'Please contact the administrator.',
];
