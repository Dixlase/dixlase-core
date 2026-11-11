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
    | Source Code URL (AGPL §13 Compliance)
    |--------------------------------------------------------------------------
    |
    | The URL where users can obtain the source code of the running
    | Dixlase instance, including any modifications. This is
    | required by AGPL v3 Section 13 when Dixlase is made available
    | over a network.
    |
    | Default: The official Dixlase repository.
    | Operators running a modified version MUST override this to point
    | to the repository (or equivalent distribution) of their running code.
    |
    */

    'source_url' => env('DIXLASE_SOURCE_URL', 'https://github.com/Dixlase/dixlase-core'),

    /*
    |--------------------------------------------------------------------------
    | Install Method
    |--------------------------------------------------------------------------
    |
    | How this copy of Dixlase was installed: "docker", "oneliner" or
    | "composer". The installers set it; a release ZIP or a git clone
    | leaves it unset. The setup wizard reads it to choose its defaults
    | (MySQL or SQLite, Mailpit or an empty mail host) and its guidance.
    | See App\Services\Install\InstallEnvironment.
    |
    */

    'install_method' => env('DIXLASE_INSTALL_METHOD'),

    /*
    |--------------------------------------------------------------------------
    | Mailpit URL
    |--------------------------------------------------------------------------
    |
    | Where the browser opens the Mailpit web interface, e.g.
    | "http://localhost:8025". The Docker installer knows the port it
    | publishes Mailpit on and can set this; when it is unset, the setup
    | wizard links to the current host on port 8025.
    |
    */

    'mailpit_url' => env('DIXLASE_MAILPIT_URL'),

    /*
    |--------------------------------------------------------------------------
    | License Label
    |--------------------------------------------------------------------------
    |
    | The short license identifier shown in the admin and public footers.
    | This should reflect the license under which you distribute the
    | running version. It does NOT change the actual license terms;
    | modify your LICENSE file if you need a different license.
    |
    */

    'license_label' => env('DIXLASE_LICENSE_LABEL', 'AGPLv3'),

    'license_url' => env('DIXLASE_LICENSE_URL', 'https://www.gnu.org/licenses/agpl-3.0.html'),

    /*
    |--------------------------------------------------------------------------
    | Demo Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, the DemoGuard middleware blocks destructive admin
    | actions (plugin / theme install / uninstall, mail server config,
    | maintenance toggle, etc.) and the application forces the default
    | mail transport to "log" so inquiry forms and notification emails
    | are written to the log file instead of being delivered.
    |
    | Intended for public demo deployments (e.g., demo.dixlase.org) where
    | many anonymous visitors share the same Dixlase install and must not
    | be able to mutate state that affects other tenants or send mail
    | from the host's domain.
    |
    */

    'demo_mode' => filter_var(env('DIXLASE_DEMO_MODE', false), FILTER_VALIDATE_BOOLEAN),

    /*
    |--------------------------------------------------------------------------
    | Demo Account Hint
    |--------------------------------------------------------------------------
    |
    | Optional "username / password" string surfaced in the demo banner so a
    | visitor knows how to sign in (e.g. "demo / demo1234"). Public by design;
    | leave empty to hide the account line. Only shown when demo_mode is on.
    |
    */

    'demo_account' => (string) env('DIXLASE_DEMO_ACCOUNT', ''),

    /*
    |--------------------------------------------------------------------------
    | Page Title Composition
    |--------------------------------------------------------------------------
    |
    | How Core composes the document title (see
    | App\Services\Site\PageTitleBuilder). The site root leads with the
    | site name followed by the tagline; every other page leads with the
    | page name, because search results truncate the tail and the
    | page-specific words have to survive.
    |
    | Available placeholders: :site, :tagline, :page, :separator.
    |
    */

    'page_title' => [
        'separator' => ' - ',
        'root_format' => ':site:separator:tagline',
        'page_format' => ':page:separator:site',
    ],

];
