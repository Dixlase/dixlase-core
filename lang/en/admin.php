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
        'super_manager' => 'Super Administator',
        'manager' => 'Administator',
        'editor' => 'Editor',
        'receptionist' => 'Reception',
        'viewer' => 'Viewer',
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
