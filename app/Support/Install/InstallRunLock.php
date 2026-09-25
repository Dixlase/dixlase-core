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
 * Marks an install execution that is under way.
 *
 * `InstallConfirmController::store()` runs for a minute or more: it
 * rewrites `.env`, migrates, seeds, installs the bundled theme and
 * creates the first administrator. Nothing used to stop a second run
 * from starting in the middle of the first — a double-submitted form, an
 * impatient reload, or a proxy retry was enough — and two concurrent
 * `migrate:fresh` calls against one database is the worst possible way
 * to find that out.
 *
 * The lock also records the fact that a run was interrupted. A process
 * killed mid-install (the built-in server restarting on the `.env`
 * write, a request timeout, a closed tab) leaves the marker behind, and
 * the next attempt can say so instead of pretending nothing happened.
 *
 * Like FinalizePendingMarker this is a file rather than a session entry:
 * the point is to survive the request, and during an install the session
 * itself is being rebuilt.
 */
class InstallRunLock
{
    /**
     * How long a marker counts as "a run in progress".
     *
     * Long enough for a slow install on modest shared hosting (migrations
     * plus seeds plus the theme install), short enough that a killed run
     * does not block a retry for the rest of the day.
     */
    public const TTL_SECONDS = 1800;

    /**
     * Take the lock, or report that another run holds it.
     *
     * A marker older than the TTL is treated as the remains of an
     * interrupted run: it is taken over rather than honoured.
     */
    public static function acquire(): bool
    {
        if (self::isRunning()) {
            return false;
        }

        $path = self::path();
        $dir = dirname($path);

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return @file_put_contents($path, (string) time(), LOCK_EX) !== false;
    }

    /**
     * Is another execution running right now?
     */
    public static function isRunning(): bool
    {
        $started = self::startedAt();

        return $started !== null && (time() - $started) < self::TTL_SECONDS;
    }

    /**
     * Did an earlier execution stop without finishing?
     *
     * True only for a marker older than the TTL — while a run is inside
     * the window it is "running", not "interrupted".
     */
    public static function wasInterrupted(): bool
    {
        $started = self::startedAt();

        return $started !== null && (time() - $started) >= self::TTL_SECONDS;
    }

    /**
     * When the current marker was written, or null when there is none.
     */
    public static function startedAt(): ?int
    {
        $path = self::path();

        if (! is_file($path)) {
            return null;
        }

        $written = @filemtime($path);

        // An unreadable timestamp must not block every later attempt.
        return $written === false ? null : $written;
    }

    /**
     * Drop the marker once the run has finished, successfully or not.
     */
    public static function release(): void
    {
        $path = self::path();

        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Where the marker lives.
     *
     * `storage/framework/` is writable on every supported install and is
     * already excluded from backups and deploy syncs, so a stray marker
     * never travels between environments.
     */
    public static function path(): string
    {
        return storage_path('framework/install-running');
    }
}
