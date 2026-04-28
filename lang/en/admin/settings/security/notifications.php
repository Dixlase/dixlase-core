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
    'heading' => 'Error Notification Settings',
    'title' => 'System Error Notification Settings',
    'description' => 'Configure notification settings for when system errors or application issues occur.',
    'enabled' => 'Error Notification Feature',
    'enabled_help' => 'Configure whether to send email notifications when system errors occur.',
    'log_levels' => 'Log Levels to Notify',
    'log_levels_help' => 'Select log levels to send notifications for. By notifying only high-importance errors, you can receive only necessary information.',
    'system_admin_email_required' => 'To receive error notifications, please set the system administrator email address in <a href=":url" class="text-blue-600 dark:text-blue-400 hover:underline">mail settings</a>.',
    'mail_test_required' => 'To use the error notification function, please complete mail server settings and mail tests in <a href=":url" class="text-blue-600 dark:text-blue-400 hover:underline">mail settings</a>.',
    'log_level_options' => [
        'emergency' => 'Emergency - System is unusable',
        'alert' => 'Alert - Immediate action required',
        'critical' => 'Critical - Critical situation',
        'error' => 'Error - Error situation but continues operating',
        'warning' => 'Warning - Warning level issue',
        'notice' => 'Notice - Normal but noteworthy situation',
        'info' => 'Info - General information message',
        'debug' => 'Debug - Debug information',
    ],
    'settings_updated' => 'Error notification settings have been updated.',
];
