<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
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
