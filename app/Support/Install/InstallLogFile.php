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
 * The installer's own log file, and when it stops being one.
 *
 * `storage/logs/install.log` is written while the wizard runs. Its whole
 * audience is the operator who is still in that flow: once the install
 * has completed, either the site speaks for itself or the operator is
 * reading the log while the wizard is still open. Afterwards the file is
 * a leftover that `storage/logs` carries into every backup taken with
 * logs included, and into wherever that archive is copied.
 *
 * Sites installed with 0.2.0 or earlier hold a file that also recorded
 * values it should not have (fixed in #846, recorded in
 * GHSA-p76r-g9mq-w6j6). Those sites were asked to delete it by hand;
 * discarding it on upgrade does it for them.
 *
 * Nothing here decides *when* that moment is. Three callers do:
 * the finalizer (a completed install), the core updater (an upgrade),
 * and the backup service, which simply never collects the file.
 */
class InstallLogFile
{
    /**
     * The log's name inside `storage/logs`.
     *
     * The backup service matches on this, because it projects the
     * directory rather than resolving a channel.
     */
    public const BASENAME = 'install.log';

    /**
     * Remove the log if it is there.
     *
     * Returns true only when a file was actually removed, so a caller
     * can report the one case worth reporting. Absent is the normal
     * state and not a failure; an unwritable directory is not one
     * either — the file is a leftover, and refusing to continue over it
     * would turn a tidy-up into an outage.
     */
    public static function discard(): bool
    {
        $path = self::path();

        if ($path === null || ! is_file($path)) {
            return false;
        }

        return @unlink($path);
    }

    /**
     * Where the log lives, or null when the channel does not write to a
     * file at all.
     *
     * Resolved from the `install` channel's own configuration rather
     * than hardcoded, so this class cannot drift away from the file
     * Monolog actually writes. A channel pointed at stderr, or replaced
     * with a stack, has no single path — and then there is nothing for
     * this class to remove.
     */
    public static function path(): ?string
    {
        $path = config('logging.channels.install.path');

        return is_string($path) && $path !== '' ? $path : null;
    }
}
