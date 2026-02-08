<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
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
    | Default Captcha Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default captcha driver that will be used
    | for verification. You may set this to any of the drivers defined
    | in the "drivers" array below.
    |
    */

    'default' => env('CAPTCHA_DRIVER', 'google'),

    /*
    |--------------------------------------------------------------------------
    | Captcha Drivers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the captcha drivers for your application.
    | Each driver has its own configuration options.
    |
    */

    'drivers' => [
        'google' => [
            'class' => \App\Captcha\GoogleRecaptchaDriver::class,
            'site_key' => env('RECAPTCHA_SITE_KEY'),
            'secret_key' => env('RECAPTCHA_SECRET_KEY'),
            'version' => env('RECAPTCHA_VERSION', 'v3'), // 'v2_checkbox', 'v2_invisible', 'v3'
            'min_score' => env('RECAPTCHA_MIN_SCORE', 0.5),
        ],

        'turnstile' => [
            'class' => \App\Captcha\TurnstileCaptchaDriver::class,
            'site_key' => env('TURNSTILE_SITE_KEY'),
            'secret_key' => env('TURNSTILE_SECRET_KEY'),
        ],

        // Future drivers can be added here
        // 'hcaptcha' => [
        //     'class' => \App\Captcha\HCaptchaDriver::class,
        //     'site_key' => env('HCAPTCHA_SITE_KEY'),
        //     'secret_key' => env('HCAPTCHA_SECRET_KEY'),
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bypass for Testing
    |--------------------------------------------------------------------------
    |
    | When this is set to true, all captcha verifications will pass.
    | This is useful for testing environments.
    |
    */

    'bypass' => env('CAPTCHA_BYPASS', false),

    /*
    |--------------------------------------------------------------------------
    | Form Definitions
    |--------------------------------------------------------------------------
    |
    | Define all forms that can use CAPTCHA verification.
    | Each form has:
    | - name: Translation key for display name
    | - route: Route name where the form is submitted
    | - category: Category for grouping in UI
    | - default_enabled: Default state when first registered
    | - priority: Display order (lower = higher priority)
    |
    */

    'forms' => [
        'admin_login' => [
            'name' => 'admin/settings/security/captcha.forms.admin_login',
            'route' => 'admin.login',
            'category' => 'admin',
            'default_enabled' => false,
            'priority' => 10,
        ],
        'admin_password_reset' => [
            'name' => 'admin/settings/security/captcha.forms.admin_password_reset',
            'route' => 'admin.password.request',
            'category' => 'admin',
            'default_enabled' => false,
            'priority' => 20,
        ],
        'admin_two_fa' => [
            'name' => 'admin/settings/security/captcha.forms.admin_two_fa',
            'route' => 'admin.two-fa.email.verify',
            'category' => 'admin',
            'default_enabled' => false,
            'priority' => 30,
        ],
    ],
];
