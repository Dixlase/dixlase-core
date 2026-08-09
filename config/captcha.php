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

/*
|--------------------------------------------------------------------------
| Where CAPTCHA is actually configured
|--------------------------------------------------------------------------
|
| Almost nothing about CAPTCHA is configured here. Provider selection and
| credentials live in the DATABASE and are edited through the admin panel
| (Settings -> Security -> CAPTCHA); they are read via SecuritySetting:
|
|   provider        SecuritySetting 'captcha_driver'
|                   (CaptchaFailoverService::getActiveProvider())
|   site/secret key SecuritySetting '{provider}_site_key' / '_secret_key'
|                   (CaptchaFailoverService::getProviderConfig())
|   reCAPTCHA v2/v3 SecuritySetting 'captcha_google_version'
|   minimum score   SecuritySetting 'captcha_google_min_score'
|   Enterprise id   SecuritySetting 'captcha_google_project_id'
|
| Which driver class runs is decided by a switch in CaptchaServiceProvider,
| not by config. Adding a provider means editing that switch — a 'class'
| entry here would have no effect.
|
| Temporary bypass is a runtime state held in the cache and driven by
| CaptchaBypassService / dls:captcha:bypass, not a config value.
|
| This file previously also declared 'default', per-driver 'class',
| 'site_key', 'secret_key', 'version', 'min_score' and 'bypass'. Not one of
| them was ever read: a repository-wide search finds exactly two reads of
| config('captcha.*') — 'drivers.turnstile.theme' and 'forms', both kept
| below. The stale entries were removed because settings that are silently
| ignored are worse than settings that are absent: CAPTCHA_DRIVER,
| RECAPTCHA_SITE_KEY and friends in a .env looked authoritative and did
| nothing. 'drivers.google.class' also pointed at App\Captcha\
| GoogleRecaptchaDriver, a class deleted when the Google driver was split
| into V2/V3/Enterprise — which is how PHPStan found this.
|
*/

return [
    /*
    |--------------------------------------------------------------------------
    | Captcha Drivers
    |--------------------------------------------------------------------------
    |
    | Presentation-only options that have no place in the database because
    | they describe how a widget is drawn rather than how it authenticates.
    | Credentials are NOT here — see the note at the top of this file.
    |
    */

    'drivers' => [
        'turnstile' => [
            /*
            | Widget colour scheme. Three-tier resolution applied in
            | the driver:
            |
            |   1. `TURNSTILE_THEME` in `.env` (this value) — explicit
            |      operator override. Wins when set.
            |   2. The active theme's `SiteAppearanceProviderInterface`
            |      binding, if any — lets the widget track an in-theme
            |      light/dark/auto setting automatically.
            |   3. `auto` — Cloudflare Turnstile then follows the
            |      visitor's OS `prefers-color-scheme` setting.
            |
            | Leave this unset to let the active theme drive the widget.
            | Set it to `light`, `dark`, or `auto` to pin the widget
            | regardless of the theme's preference.
            */
            'theme' => env('TURNSTILE_THEME'),
        ],

        // A new provider is added by extending the switch in
        // CaptchaServiceProvider and storing its credentials through the
        // admin panel. Only add an entry here if the provider needs a
        // presentation option like the Turnstile theme above.
    ],

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
