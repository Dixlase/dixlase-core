<?php

/**
 * This file is part of MySoftware.
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
    | Form-specific Settings
    |--------------------------------------------------------------------------
    |
    | Configure which forms should use captcha verification.
    |
    */

    'forms' => [
        'contact' => env('CAPTCHA_CONTACT_FORM', true),
        'registration' => env('CAPTCHA_REGISTRATION_FORM', true),
        'login' => env('CAPTCHA_LOGIN_FORM', false),
        'comment' => env('CAPTCHA_COMMENT_FORM', true),
    ],
];
