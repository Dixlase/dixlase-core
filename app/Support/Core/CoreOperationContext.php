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

namespace App\Support\Core;

use App\Models\AuditLog;
use App\Services\Core\CoreUpdater;
use Illuminate\Http\Request;

/**
 * Carries the request a core update or rollback was started from into the
 * detached process that performs it.
 *
 * The admin panel does not update the core in the request. It spawns
 * `dls:core:update` / `dls:core:rollback` and returns, so the process that
 * writes the audit entry has no request: `ip_address`, `user_agent` and
 * `session_id` resolve to null and `actor_source` resolves to `CLI`,
 * which is true of the process and false of the operation. An audit log
 * kept to answer "who replaced the core, and from where" could not
 * answer it for the largest administrative action there is.
 *
 * The carrier is the lock file the panel already writes before spawning
 * (`CoreUpdater::inProgressFlagPath()`), not the command line. The user
 * agent is a value the client chooses, and a command line is visible to
 * every process on the host; a file in `storage/app/private` is not, and
 * the panel is already writing to it.
 *
 * A run started directly from a shell has no lock file, so nothing is
 * added and the entry keeps saying `CLI` — which is then the truth.
 */
class CoreOperationContext
{
    /**
     * Longest user agent stored.
     *
     * The client chooses this string, so it is capped rather than
     * trusted. 512 is past every real browser's and still far below
     * anything that would bloat a log row.
     */
    public const USER_AGENT_MAX_LENGTH = 512;

    /**
     * The captured fields, or null before the first capture.
     *
     * @var array<string, string>|null
     */
    private static ?array $captured = null;

    /**
     * The request's own identifying values, for the lock file payload.
     *
     * Called in the request that spawns the operation.
     *
     * @return array<string, string>
     */
    public static function forLockPayload(Request $request): array
    {
        $fields = [];

        if (($ip = $request->ip()) !== null) {
            $fields['ip_address'] = $ip;
        }

        if (($agent = $request->userAgent()) !== null && $agent !== '') {
            $fields['user_agent'] = mb_substr($agent, 0, self::USER_AGENT_MAX_LENGTH);
        }

        if ($request->hasSession()) {
            $fields['session_id'] = $request->session()->getId();
        }

        if (($requestId = $request->header('X-Request-ID')) !== null && $requestId !== '') {
            $fields['request_id'] = mb_substr($requestId, 0, self::USER_AGENT_MAX_LENGTH);
        }

        return $fields;
    }

    /**
     * Read the lock file once, before anything removes it.
     *
     * Called at the top of the operation. The lock file is unlinked
     * partway through — in the preflight refusal and in the `finally` of
     * the update — while the audit entries are written at several points
     * either side of that, so the values are taken once and held rather
     * than re-read when they are needed.
     */
    public static function capture(): void
    {
        self::$captured = self::readLockPayload();
    }

    /**
     * The fields to add to an audit entry, or none when the operation did
     * not come from a request.
     *
     * Captures on first use, so an audit entry written without a capture
     * still carries the context when the lock file is still there.
     *
     * @return array<string, string>
     */
    public static function auditFields(): array
    {
        if (self::$captured === null) {
            self::capture();
        }

        $fields = self::$captured ?? [];

        if ($fields === []) {
            return [];
        }

        // Stated explicitly: the process is a CLI process, so
        // AuditService would otherwise classify the operation as one.
        $fields['actor_source'] = AuditLog::ACTOR_SOURCE_WEB;

        return $fields;
    }

    /**
     * Drop the captured values, so a second operation in the same process
     * does not inherit the first one's. Tests use it too.
     */
    public static function forget(): void
    {
        self::$captured = null;
    }

    /**
     * The four fields, as the lock file holds them.
     *
     * Everything is validated here rather than trusted: the file is
     * written by the panel, but it lives on disk between two processes,
     * and `user_agent` reached it from a client.
     *
     * @return array<string, string>
     */
    private static function readLockPayload(): array
    {
        $path = CoreUpdater::inProgressFlagPath();

        if (! is_file($path)) {
            return [];
        }

        $raw = @file_get_contents($path);

        if (! is_string($raw) || $raw === '') {
            return [];
        }

        $payload = json_decode($raw, true);

        if (! is_array($payload)) {
            return [];
        }

        $fields = [];

        foreach (['ip_address', 'user_agent', 'session_id', 'request_id'] as $key) {
            $value = $payload[$key] ?? null;

            if (is_string($value) && $value !== '') {
                $fields[$key] = mb_substr($value, 0, self::USER_AGENT_MAX_LENGTH);
            }
        }

        return $fields;
    }
}
