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
        'additional_conditions' => 'expired',
    ],

    'recovery_codes' => [
        'table' => 'members_two_fa_recovery_codes',
        'date_column' => 'created_at',
        'default_days' => 90,
        'name' => 'admin/settings/systems/database.recovery_codes.name',
        'description' => 'admin/settings/systems/database.recovery_codes.description',
        'enabled' => true,
        'additional_conditions' => 'used',
    ],

    'passkeys' => [
        'table' => 'webauthn_credentials',
        'date_column' => 'created_at',
        'default_days' => 365,
        'name' => 'admin/settings/systems/database.passkeys.name',
        'description' => 'admin/settings/systems/database.passkeys.description',
        'enabled' => true,
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

    'backup_records' => [
        'table' => 'backup_records',
        'date_column' => 'created_at',
        'default_days' => 365,
        'name' => 'admin/settings/systems/database.backup_records.name',
        'description' => 'admin/settings/systems/database.backup_records.description',
        'enabled' => true,
        'additional_conditions' => 'expired_or_deleted',
    ],

    'restore_records' => [
        'table' => 'restore_records',
        'date_column' => 'restored_at',
        'default_days' => 365,
        'name' => 'admin/settings/systems/database.restore_records.name',
        'description' => 'admin/settings/systems/database.restore_records.description',
        'enabled' => true,
    ],

    'audit_logs' => [
        'table' => 'audit_logs',
        'date_column' => 'occurred_at',
        'default_days' => 365,
        'name' => 'admin/settings/systems/database.audit_logs.name',
        'description' => 'admin/settings/systems/database.audit_logs.description',
        'enabled' => true,
    ],

    'api_request_logs' => [
        'table' => 'api_request_logs',
        'date_column' => 'requested_at',
        'default_days' => 90,
        'name' => 'admin/settings/systems/database.api_request_logs.name',
        'description' => 'admin/settings/systems/database.api_request_logs.description',
        'enabled' => true,
    ],

    'cache_data' => [
        'table' => 'cache',
        'date_column' => 'expiration',
        'default_days' => null,
        'name' => 'admin/settings/systems/database.cache_data.name',
        'description' => 'admin/settings/systems/database.cache_data.description',
        'enabled' => true,
        'date_column_type' => 'timestamp',
        'additional_conditions' => 'expired_cache',
    ],
];
