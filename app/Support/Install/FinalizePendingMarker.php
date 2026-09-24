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
 * Marks the window between the install completion screen and finalize().
 *
 * Between those two points `INSTALLED=false` is the correct state even
 * though the database already shows a completed install: the operator is
 * looking at the completion screen and has not pressed a button yet. The
 * self-heal in CheckInstallationReady exists for a different situation —
 * a `.env` lost or overwritten externally — and cannot tell the two apart
 * from the flag alone.
 *
 * Path alone is not enough to tell them apart either. The completion
 * screen is a page, so the browser immediately requests its assets and
 * favicon, and those are *not* `install/*` requests: they satisfy the
 * self-heal condition, the flag is flipped, and the finalize POST that
 * follows is then rejected as "the installer of an already-installed
 * site" and redirected to the front page. finalize() never runs, so the
 * session driver is never restored and the install-time audit log is
 * never chained.
 *
 * The marker is a file rather than a session entry on purpose: the
 * request that trips the self-heal is often an asset request that
 * carries no usable session, and the flag it writes is process-wide.
 *
 * It expires. An operator who closes the tab on the completion screen
 * must not leave the self-heal disabled forever — after the TTL the
 * middleware behaves exactly as it did before this class existed.
 */
class FinalizePendingMarker
{
    /**
     * How long the completion screen keeps the self-heal at bay.
     *
     * Long enough that reading the screen, copying the admin URL and
     * storing it in a password manager never races the timer; short
     * enough that an abandoned install heals itself within the hour.
     */
    public const TTL_SECONDS = 3600;

    /**
     * Record that the completion screen is waiting for a button press.
     */
    public static function mark(): void
    {
        $path = self::path();
        $dir = dirname($path);

        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        @file_put_contents($path, (string) time(), LOCK_EX);
    }

    /**
     * Is a finalize still expected, and recent enough to honour?
     */
    public static function isPending(): bool
    {
        $path = self::path();

        if (! is_file($path)) {
            return false;
        }

        $written = @filemtime($path);

        if ($written === false) {
            // Unreadable timestamp: treat as expired rather than let a
            // broken marker keep the self-heal switched off.
            return false;
        }

        return (time() - $written) < self::TTL_SECONDS;
    }

    /**
     * Drop the marker once finalize() has done its work.
     */
    public static function clear(): void
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
        return storage_path('framework/install-finalize-pending');
    }
}
