<?php

/**
 * This file is part of Dixlase.
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
    | Core Database Cleanup Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration defines which database tables can be cleaned up
    | through the admin interface and their default retention periods.
    |
    */

    'login_attempts' => [
        'table' => 'members_login_attempts',
        'date_column' => 'created_at',
        'default_days' => 30,
        'name' => 'admin/settings/systems/database.login_attempts.name',
        'description' => 'admin/settings/systems/database.login_attempts.description',
        'enabled' => true,
    ],

    'password_reset_tokens' => [
        'table' => 'members_password_reset_tokens',
        'date_column' => 'created_at',
        'default_days' => 30,
        'name' => 'admin/settings/systems/database.password_reset_tokens.name',
        'description' => 'admin/settings/systems/database.password_reset_tokens.description',
        'enabled' => true,
    ],

    'two_fa_attempts' => [
        'table' => 'members_two_fa_attempts',
        'date_column' => 'created_at',
        'default_days' => 30,
        'name' => 'admin/settings/systems/database.two_fa_attempts.name',
        'description' => 'admin/settings/systems/database.two_fa_attempts.description',
        'enabled' => true,
    ],

    'two_fa_tokens' => [
        'table' => 'members_two_fa_tokens',
        'date_column' => 'created_at',
        'default_days' => 7,
        'name' => 'admin/settings/systems/database.two_fa_tokens.name',
        'description' => 'admin/settings/systems/database.two_fa_tokens.description',
        'enabled' => true,
        'additional_conditions' => function ($query) {
            return $query->orWhere('expires_at', '<', now());
        },
    ],

    'recovery_codes' => [
        'table' => 'members_recovery_codes',
        'date_column' => 'created_at',
        'default_days' => 90,
        'name' => 'admin/settings/systems/database.recovery_codes.name',
        'description' => 'admin/settings/systems/database.recovery_codes.description',
        'enabled' => true,
        'additional_conditions' => function ($query) {
            return $query->orWhere('used_at', '!=', null);
        },
    ],

    'passkeys' => [
        'table' => 'webauthn_credentials',
        'date_column' => 'last_used_at',
        'default_days' => 365,
        'name' => 'admin/settings/systems/database.passkeys.name',
        'description' => 'admin/settings/systems/database.passkeys.description',
        'enabled' => true,
        'additional_conditions' => function ($query) {
            return $query->whereNull('last_used_at')
                ->where('created_at', '<', now()->subDays(90));
        },
    ],

    'sessions' => [
        'table' => 'sessions',
        'date_column' => 'last_activity',
        'default_days' => 7,
        'name' => 'admin/settings/systems/database.sessions.name',
        'description' => 'admin/settings/systems/database.sessions.description',
        'enabled' => true,
        'date_column_type' => 'timestamp',
    ],

    'cache_data' => [
        'table' => 'cache',
        'date_column' => 'expiration',
        'default_days' => null,
        'name' => 'admin/settings/systems/database.cache_data.name',
        'description' => 'admin/settings/systems/database.cache_data.description',
        'enabled' => true,
        'date_column_type' => 'timestamp',
        'additional_conditions' => function ($query) {
            return $query->where('expiration', '<', now()->timestamp);
        },
    ],
];
