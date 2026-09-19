<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
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

    /*
    |--------------------------------------------------------------------------
    | Software Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your software, which will be used when the
    | framework needs to place the software's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */
    'software_name' => env('SOFTWARE_NAME', 'Dixlase'),

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Dixlase'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Audit Log Signing Key
    |--------------------------------------------------------------------------
    |
    | HMAC key for the audit log daily seals. When empty the application key
    | is used, so the seals detect tampering in the database but not by an
    | attacker who can also read .env. Set a separate secret to widen that,
    | but set it before the first seal exists: seals signed with a previous
    | key fail verification (key rotation is not supported yet).
    |
    */

    'audit_log_secret' => env('AUDIT_LOG_SECRET'),

    'previous_keys' => [
        ...array_filter(
            explode(',', env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Force SSL
    |--------------------------------------------------------------------------
    |
    | This value determines whether the application should force SSL connections.
    | This is used to ensure that all connections are secure.
    */
    'force_ssl' => env('FORCE_SSL', false), // Retrieved from `.env`

    /*
    |--------------------------------------------------------------------------
    | Installed
    |-------------------------------------------------------------------------
    |
    | This value determines whether the application has been installed or not.
    | This is used to prevent the installer from being accessed after the
    | application has been installed.
    |
    */

    'installed' => env('INSTALLED', false),

    /*
    |--------------------------------------------------------------------------
    | Custom File Types (from custom.php)
    |--------------------------------------------------------------------------
    |
    Custom file type-specific settings
    |
    */
    'file_types' => [
        'routes' => [
            'path' => 'routes',
            'namespace' => '',
            'naming_convention' => 'snake_case',
        ],
        'config' => [
            'path' => 'config',
            'namespace' => '',
            'naming_convention' => 'snake_case',
        ],
        'lang' => [
            'path' => 'lang',
            'namespace' => '',
            'naming_convention' => 'snake_case',
        ],
        'controllers' => [
            'path' => 'app/Http/Controllers',
            'namespace' => 'App\\Http\\Controllers\\',
            'naming_convention' => 'studly_case',
        ],
        'models' => [
            'path' => 'app/Models',
            'namespace' => 'App\\Models\\',
            'naming_convention' => 'studly_case',
        ],
        'middleware' => [
            'path' => 'app/Http/Middleware',
            'namespace' => 'App\\Http\\Middleware\\',
            'naming_convention' => 'studly_case',
        ],
        'events' => [
            'path' => 'app/Events',
            'namespace' => 'App\\Events\\',
            'naming_convention' => 'studly_case',
        ],
        'jobs' => [
            'path' => 'app/Jobs',
            'namespace' => 'App\\Jobs\\',
            'naming_convention' => 'studly_case',
        ],
        'policies' => [
            'path' => 'app/Policies',
            'namespace' => 'App\\Policies\\',
            'naming_convention' => 'studly_case',
        ],
        'migrations' => [
            'path' => 'database/migrations',
            'namespace' => '',
            'naming_convention' => 'snake_case_with_timestamp',
        ],
        'views' => [
            'path' => 'resources/views',
            'namespace' => '',
            'naming_convention' => 'kebab_case',
        ],
    ],
];
