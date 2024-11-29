<?php

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
            'systems' => 'System Settings'
        ],
    ],

    'pages' => [
        'settings' => [
            'admins' => [
                'create' => [
                    'title' => 'Create Admin',
                    'name' => 'Name',
                    'email' => 'Email',
                    'password' => 'Password',
                    'password_confirmation' => 'Password Confirmation',
                    'role' => 'Role',
                    'submit' => 'Submit',
                ],
                'edit' => [
                    'title' => 'Edit Admin',
                    'name' => 'Name',
                    'email' => 'Email',
                    'password' => 'Password',
                    'password_confirmation' => 'Password Confirmation',
                    'role' => 'Role',
                    'submit' => 'Update',
                ],
                'profile' => [
                    'title' => 'Profile',
                    'name' => 'Name',
                    'email' => 'Email',
                    'password' => 'Password',
                    'password_confirmation' => 'Password Confirmation',
                    'role' => 'Role',
                    'submit' => 'Update',
                ],
            ],
            'systems' => [
                'title' => 'System Settings',
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
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'editor' => 'Editor',
        'receptionist' => 'Reception',
    ],

    'permissions' => 'Permissions',
    'settings' => 'Settings',
    'logout' => 'Logout',


];
