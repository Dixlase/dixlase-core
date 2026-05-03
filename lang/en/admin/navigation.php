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
    'edit_menu' => 'Edit Menu',
    'done_editing' => 'Done',
    'reset_menu' => 'Reset',
    'reset_confirm_title' => 'Reset Menu',
    'reset_confirm_message' => 'All visibility and ordering settings will be reset. This action cannot be undone.',
    'reset_confirm_button' => 'Reset',
    'reset_cancel_button' => 'Cancel',

    'dashboard' => 'Dashboard',
    'front' => [
        'text' => 'Front Page Management',
        'index' => 'Front Page Master',
        'edit' => 'Front Page Edit',
        'settings' => 'Front Page Settings',
    ],
    'media' => [
        'text' => 'Media Management',
        'index' => 'Media Master',
        'upload' => 'Media Upload',
        'settings' => 'Media Settings',
    ],
    'profile' => [
        'text' => 'Profile Settings',
        'index' => 'Overview',
        'basic' => 'Basic Information',
        'password' => 'Password Settings',
        'appearance' => 'Appearance Settings',
        'notifications' => 'Notification Settings',
        'two_fa' => 'Two-Factor Auth',
        'two_fa_management' => 'Passkey & Code',
    ],
    'settings' => [
        'text' => 'Global Settings',
        'base' => [
            'text' => 'Basic Settings',
            'index' => 'Overview',
            'site' => 'Site Settings',
            'admin' => 'Admin Panel Settings',
            'mail' => 'Mail Settings',
            'maintenance' => 'Maintenance Settings',
            'mode' => 'Mode Settings',
            'content' => 'Content Settings',
            'editor' => 'GUI Editor Settings',
        ],
        'security' => [
            'text' => 'Security Settings',
            'index' => 'Overview',
            'password' => 'Password',
            'login_attempt' => 'Login',
            'session' => 'Session',
            'two-fa' => 'Two-Factor Authentication',
            'captcha' => 'CAPTCHA',
            'ip' => 'IP Access Control',
            'extensions' => 'Extensions',
            'csp' => 'CSP',
            'notifications' => 'Error Notifications',
            'environment' => 'Environment',
            'integrity' => 'File Integrity',
        ],
        'members' => [
            'text' => 'Member Management',
            'index' => 'Member Master',
            'create' => 'Member Create',
            'edit' => 'Edit',
            'profile' => 'Profile Settings',
            'roles' => 'Member Role Settings',
            'roles_short' => 'Role Settings',
            'settings' => 'Member Global Settings',
            'overview' => 'Overview',
            'settings_nav' => [
                'password' => 'Password Settings',
                'session' => 'Session Settings',
                'auth' => 'Authentication Settings',
            ],
        ],
        'themes' => [
            'text' => 'Theme Management',
            'index' => 'Theme Master',
            'index_page' => [
                'heading' => 'Theme Master',
                'installed_heading' => 'Installed Themes',
                'uninstalled_heading' => 'Uninstalled Themes',
                'table' => [
                    'caption' => 'Theme List',
                    'id' => 'ID',
                    'name' => 'Theme Name',
                ],
                'uninstalled_table' => [
                    'caption' => 'Uninstalled Theme List',
                ],
                'no_themes' => 'No themes available',
                'uninstall' => [
                    'confirm_title' => 'Uninstall Theme',
                    'confirm_message' => 'Are you sure you want to uninstall "{name}"?',
                ],
                'add' => [
                    'confirm_title' => 'Add Theme',
                    'confirm_message' => 'Are you sure you want to add "{name}"?',
                ],
                'delete' => [
                    'confirm_title' => 'Delete Theme',
                    'confirm_message' => 'Are you sure you want to permanently delete all files and folders for "{name}"? This action cannot be undone.',
                ],
            ],
            'add' => 'Add',
            'settings' => 'Theme Settings',
        ],
        'plugins' => [
            'text' => 'Plugin Management',
            'index' => 'Plugin Master',
            'add' => 'Add',
            'index_page' => [
                'heading' => 'Plugin Master',
                'installed_heading' => 'Installed Plugins',
                'uninstalled_heading' => 'Uninstalled Plugins',
                'table' => [
                    'caption' => 'Plugin List',
                    'id' => 'ID',
                    'name' => 'Plugin Name',
                ],
                'uninstalled_table' => [
                    'caption' => 'Uninstalled Plugin List',
                ],
                'no_plugins' => 'No plugins available',
                'uninstall' => [
                    'confirm_title' => 'Uninstall Plugin',
                    'confirm_message' => 'Are you sure you want to uninstall "{name}"?',
                    'remove_data_checkbox' => 'Also remove database data',
                ],
                'install' => [
                    'confirm_title' => 'Install Plugin',
                    'confirm_message' => 'Are you sure you want to install "{name}"?',
                ],
                'delete' => [
                    'confirm_title' => 'Delete Plugin',
                    'confirm_message' => 'Are you sure you want to permanently delete all files and folders for "{name}"? This action cannot be undone.',
                ],
            ],
            'install' => 'Install',
        ],
        'systems' => [
            'text' => 'System',
            'updates' => 'Updates',
            'api' => 'API Management',
            'cache' => 'Cache',
            'database' => 'Database',
            'backup' => [
                'text' => 'Backup',
                'index' => 'Backups',
                'restores' => 'Restore History',
                'settings' => 'Backup Settings',
            ],
            'logs' => [
                'text' => 'Log Management',
                'audit' => 'Audit Logs',
                'files' => 'File Logs',
            ],
            'info' => 'System Information',
        ],
    ],
];
