<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
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

    /*
        |--------------------------------------------------------------------------
        | Admin Language Lines
        |--------------------------------------------------------------------------
        |
        | The following language lines are used during admin for various
        | messages that we need to display to the user. You are free to modify
        | these language lines according to your application's requirements.
        |
        */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    'nav' => [
        'dashboard' => 'Dashboard',
        'contents' => [
            'text' => 'Contents',
            'pages' => [
                'text' => 'Pages',
                'index' => 'Page Master',
                'create' => 'Create Page',
            ],
        ],
        'users' => [
            'text' => 'Users',
            'index' => 'User Master',
            'create' => 'Create User',
        ],
        'settings' => [
            'text' => 'Settings',
            'admins' => [
                'text' => 'Admin Settings',
                'index' => 'Admin Master',
                'create' => 'Create Admin',
                'profile' => 'Profile',
            ],
            'plugins' => [
                'text' => 'Plugin Settings',
                'index' => 'Plugin Master',
                'install'  => 'Install',
            ],
            'security' => 'Security Settings',
            'systems' => 'System Settings'
        ],
    ],

    'features' => [
        'contents' => [
            'pages' => [
                'index' => [
                    'heading' => 'Page Master',
                    'name' => 'Name',
                    'slug' => 'Slug',
                    'status' => 'Status',
                    'created_at' => 'Created At',
                    'updated_at' => 'Updated At',
                    'actions' => 'Actions',
                    'edit' => 'Edit',
                    'delete' => 'Delete',
                ],
                'create' => [
                    'heading' => 'Create Page',
                    'name' => 'Name',
                    'slug' => 'Slug',
                    'status' => 'Status',
                    'submit' => 'Submit',
                ],
                'edit' => [
                    'heading' => 'Edit Page',
                    'name' => 'Name',
                    'slug' => 'Slug',
                    'status' => 'Status',
                    'submit' => 'Update',
                ],
            ],
        ],
        'users' => [
            'index' => [
                'heading' => 'User Master',
            ],
            'create' => [
                'heading' => 'Create User',
            ],

        ],
        'settings' => [
            'admins' => [
                'index' => [
                    'heading' => 'Admin Master',
                ],
                'create' => [
                    'heading' => 'Create Admin',
                    'name' => 'Name',
                    'email' => 'Email',
                    'password' => 'Password',
                    'password_confirmation' => 'Password Confirmation',
                    'role' => 'Role',
                    'submit' => 'Submit',
                ],
                'edit' => [
                    'heading' => 'Edit Admin',
                    'name' => 'Name',
                    'email' => 'Email',
                    'password' => 'Password',
                    'password_confirmation' => 'Password Confirmation',
                    'role' => 'Role',
                    'submit' => 'Update',
                ],
                'profile' => [
                    'heading' => 'Profile',
                    'name' => 'Name',
                    'email' => 'Email',
                    'password' => 'Password',
                    'password_confirmation' => 'Password Confirmation',
                    'role' => 'Role',
                    'submit' => 'Update',
                ],
            ],
            'systems' => [
                'heading' => 'System Settings',
                'site_name' => 'Site Name',
                'admin_theme' => 'Admin Theme',
                'language' => 'Language',
                'is_member_site' => 'Is Member Site',
                'allow_external_registration' => 'Allow External Registration',
                'allow_guest_registration' => 'Allow Guest Registration',
                'required_fields' => 'Required Fields',
                'maintenance_mode' => 'Maintenance Mode',
                'maintenance_message' => 'Maintenance Message',
            ],
        ],

    ],




    'roles' => [
        'text' => 'Roles',
        'super_admin' => 'Super Administator',
        'admin' => 'Administator',
        'editor' => 'Editor',
        'author' => 'Author',
        'contributor' => 'Contributor',
    ],

    'theme' => [
        'auto' => 'Auto',
        'dark' => 'Dark',
        'light' => 'Light',
    ],


    'permissions' => 'Permissions',
    'settings' => 'Settings',
    'logout' => 'Logout',


];
