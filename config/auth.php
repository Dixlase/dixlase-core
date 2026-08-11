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
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option defines the default authentication "guard" and password
    | reset "broker" for your application. You may change these values
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'member'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'member'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    |
    | Next, you may define every authentication guard for your application.
    | Of course, a great default configuration has been defined for you
    | which utilizes session storage plus the Eloquent user provider.
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | Supported: "session"
    |
    */

    'guards' => [
        'member' => [
            'driver' => 'session',
            'provider' => 'members',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    |
    | All authentication guards have a user provider, which defines how the
    | users are actually retrieved out of your database or other storage
    | system used by the application. Typically, Eloquent is utilized.
    |
    | If you have multiple user tables or models you may configure multiple
    | providers to represent the model / table. These providers may then
    | be assigned to any extra authentication guards you have defined.
    |
    | Supported: "database", "eloquent"
    |
    */

    'providers' => [
        'members' => [
            'driver' => 'eloquent',
            'model' => App\Models\Member::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | These configuration options specify the behavior of Laravel's password
    | reset functionality, including the table utilized for token storage
    | and the user provider that is invoked to actually retrieve users.
    |
    | The expiry time is the number of minutes that each reset token will be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    | The throttle setting is the number of seconds a user must wait before
    | generating more password reset tokens. This prevents the user from
    | quickly generating a very large amount of password reset tokens.
    |
    */

    'passwords' => [
        'members' => [
            'provider' => 'members',
            'table' => 'members_password_reset_tokens',
            'expire' => 60,
            'throttle' => env('APP_ENV') === 'local' ? 0 : env('PASSWORD_RESET_THROTTLE', 60),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Guards, Providers and Brokers Belonging to Plugins
    |--------------------------------------------------------------------------
    |
    | Core declares the `member` guard only. A front-end account system is a
    | plugin concern, so a plugin that adds one registers its own guard,
    | provider and password broker from its service provider rather than
    | having Core name the plugin's model here:
    |
    |     config([
    |         'auth.guards.user'      => ['driver' => 'session', 'provider' => 'users'],
    |         'auth.providers.users'  => ['driver' => 'eloquent', 'model' => Foo::class],
    |         'auth.passwords.users'  => ['provider' => 'users', 'table' => '...'],
    |     ]);
    |
    | Core previously carried a `user` guard, a `users` provider pointing at
    | Plugins\DixlaseUsers\App\Models\DixlaseUsersUser, and a `users` broker
    | keyed to that plugin's table. Nothing in Core resolved any of them, and
    | the model reference broke static analysis whenever the plugin was not
    | checked out. Registering from the plugin keeps the coupling one-way.
    |
    | Note that dropping a key from this file does not make it disappear.
    | Laravel 11+ merges its own config/auth.php underneath this one, so the
    | framework defaults now show through: a `web` guard, a `users` provider
    | bound to App\Models\User (which Core does not ship), and a `users` broker
    | on `password_reset_tokens`. That merge was already happening before the
    | removal -- `web` has never been declared here, and it has always pointed
    | at whatever `providers.users` resolved to. Core resolves neither, so both
    | are inert; a plugin that declares `auth.providers.users` overrides the
    | framework default rather than colliding with it.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the amount of seconds before a password confirmation
    | window expires and users are asked to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

    'lifetime' => env('SESSION_LIFETIME', 120), // Unit: minutes

    'aliases' => [
        // Other aliases
        'Auth' => Illuminate\Support\Facades\Auth::class,
    ],

];
