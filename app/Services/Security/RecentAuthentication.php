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

namespace App\Services\Security;

use Illuminate\Contracts\Session\Session;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * Remembers, per session, when the signed-in member last confirmed who they
 * are by entering their password or using a passkey (dixlase-core#675).
 *
 * Signing in does not count. The point is that a session cookie on its own,
 * however it was obtained, is not enough for the operations the
 * `recent_auth` middleware guards, and a cookie taken right after a sign-in
 * is still a stolen cookie.
 *
 * The time is bound to the member it was confirmed for, so a confirmation
 * never carries over to another member signing in on the same session.
 */
final class RecentAuthentication
{
    public const SESSION_KEY = 'admin.recent_auth';

    /** Where to go after confirming. */
    public const INTENDED_KEY = 'admin.recent_auth_intended';

    public function __construct(private readonly Session $session) {}

    /**
     * The validity window, in minutes, from `security.recent_auth.minutes`.
     */
    public static function defaultMinutes(): int
    {
        return max(1, (int) config('security.recent_auth.minutes', 15));
    }

    public function confirm(int|string $memberId): void
    {
        $this->session->put(self::SESSION_KEY, [
            'member_id' => (string) $memberId,
            'at' => now()->getTimestamp(),
        ]);
    }

    /**
     * Whether this member confirmed within the last `$minutes` minutes.
     */
    public function isRecent(int|string $memberId, ?int $minutes = null): bool
    {
        $entry = $this->session->get(self::SESSION_KEY);

        if (! is_array($entry) || ($entry['member_id'] ?? null) !== (string) $memberId) {
            return false;
        }

        $at = $entry['at'] ?? null;

        if (! is_int($at) || $at > now()->getTimestamp()) {
            return false;
        }

        return now()->getTimestamp() - $at < ($minutes ?? self::defaultMinutes()) * 60;
    }

    public function forget(): void
    {
        $this->session->forget([self::SESSION_KEY, self::INTENDED_KEY]);
    }

    /**
     * @param  bool  $resubmit  Whether the refused request was a form submission the member has to send again
     */
    public function rememberIntended(string $url, bool $resubmit = false): void
    {
        $this->session->put(self::INTENDED_KEY, ['url' => $url, 'resubmit' => $resubmit]);
    }

    /**
     * @return array{url: string, resubmit: bool}|null
     */
    public function pullIntended(): ?array
    {
        $entry = $this->session->pull(self::INTENDED_KEY);

        if (! is_array($entry) || ! is_string($entry['url'] ?? null) || $entry['url'] === '') {
            return null;
        }

        return ['url' => $entry['url'], 'resubmit' => (bool) ($entry['resubmit'] ?? false)];
    }
}
