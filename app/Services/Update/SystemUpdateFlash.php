<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @internal Core only. Do not reference from plugins/themes
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

namespace App\Services\Update;

use Illuminate\Support\Facades\File;

/**
 * One-shot "an update just finished" marker for the System Updates page.
 *
 * Web-triggered core and extension updates run in a DETACHED subprocess,
 * which cannot set a session flash on the request that will eventually
 * render the result. The subprocess records its outcome here on completion;
 * the next AdminSystemUpdatesController::index() render consumes it (once),
 * turns it into a session flash, and deletes it.
 *
 * File-based (under storage/, a protected path) so it mirrors the in-progress
 * flag pattern and survives the mid-update source swap. A short max age keeps
 * a stray CLI run from surfacing a stale completion banner much later.
 *
 * @internal Core only. Do not reference from plugins/themes
 */
final class SystemUpdateFlash
{
    /**
     * Ignore a result older than this. A direct CLI run records a result too;
     * bounding the age means it can only surface as a flash to an operator who
     * opens the page shortly after, never days later.
     */
    private const MAX_AGE_SECONDS = 3600;

    public static function path(): string
    {
        return storage_path('app/private/.system-update-result.json');
    }

    /**
     * Record the outcome of a just-finished update. Must be called BEFORE the
     * caller clears its in-progress flag, so that once the flag is gone the
     * result is already present for index() to read.
     *
     * @param  array<string, mixed>  $result  Expects at least `status`
     *                                        ('success'|'error') and `kind`
     *                                        ('core'|'extension'), plus a
     *                                        payload used to build the message.
     */
    public static function record(array $result): void
    {
        $result['finished_at'] = now()->timestamp;

        File::ensureDirectoryExists(dirname(self::path()));
        File::put(
            self::path(),
            json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );
    }

    /**
     * Read AND delete the recorded result. Returns null when there is none, it
     * is malformed, or it is older than MAX_AGE_SECONDS (still deleted).
     *
     * @return array<string, mixed>|null
     */
    public static function consume(): ?array
    {
        $path = self::path();
        if (! is_file($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        @unlink($path);

        $data = json_decode((string) $raw, true);
        if (! is_array($data)) {
            return null;
        }

        $finishedAt = (int) ($data['finished_at'] ?? 0);
        if ($finishedAt > 0 && (time() - $finishedAt) > self::MAX_AGE_SECONDS) {
            return null;
        }

        return $data;
    }
}
