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

declare(strict_types=1);

namespace App\Support\Install;

/**
 * The session fields each install wizard step must leave behind.
 *
 * One definition for the two places that gate on it: the step middleware
 * (CheckInstallationSteps), which decides whether a later screen may open,
 * and the confirm screen (InstallConfirmController), which decides whether
 * the install may run. They used to keep separate lists, and the
 * middleware's still asked for a database host, port and user when the
 * driver was SQLite. SQLite submits none of them, so every SQLite install
 * through the browser was sent back to the database step.
 */
final class InstallStepFields
{
    /**
     * Required fields per step, in wizard order. A step absent from the map
     * (mail, confirm) has no required field.
     *
     * @param  array<string, mixed>  $data  The wizard's `install_data` session array
     * @return array<string, list<string>>
     */
    public static function all(array $data): array
    {
        return [
            'settings' => ['site_name', 'admin_account_name', 'admin_email', 'admin_password'],
            'environment' => ['app_env', 'app_url', 'admin_url', 'app_timezone'],
            'database' => self::database($data['db_connection'] ?? null),
        ];
    }

    /**
     * Required fields for one step; an empty list for a step with none.
     *
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    public static function forStep(string $step, array $data): array
    {
        return self::all($data)[$step] ?? [];
    }

    /**
     * Required database fields for the chosen driver.
     *
     * SQLite has no server to reach, so the database step neither shows nor
     * submits host, port or user name; it only needs the file path.
     *
     * @return list<string>
     */
    public static function database(?string $connection): array
    {
        if ($connection === 'sqlite') {
            return ['db_connection', 'db_database'];
        }

        return ['db_connection', 'db_host', 'db_port', 'db_database', 'db_username'];
    }
}
