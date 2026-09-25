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
 * @internal For Core use only. Do not reference from plugins/themes
 *
 * Where the SQLite database file lives, and making sure it is there.
 *
 * Laravel's SQLite connector needs a path it can `realpath()` and throws
 * for a missing file instead of creating one. The connection test, the
 * mid-wizard .env write and the installation itself all resolve the path
 * through here, so the file the test creates is the file the install
 * later opens.
 */
class SqliteDatabase
{
    /**
     * Resolve the configured path, falling back to database/database.sqlite.
     */
    public static function resolvePath(?string $database): string
    {
        $database = trim((string) $database);

        if ($database === '' || $database[0] !== '/') {
            return database_path('database.sqlite');
        }

        return $database;
    }

    /**
     * Create the file (and its directory) when missing.
     *
     * @return bool false when the file could not be created or is not writable
     */
    public static function ensureFile(string $database): bool
    {
        if (! is_file($database)) {
            @mkdir(dirname($database), 0775, true);
            @touch($database);
        }

        return is_file($database) && is_writable($database);
    }
}
