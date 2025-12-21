<?php

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
