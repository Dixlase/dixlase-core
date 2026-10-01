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

namespace App\Exceptions;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * An extension source refused a request because its rate limit is used up.
 *
 * Carries the time the limit resets so the admin screens can say when to
 * try again, instead of a bare "HTTP 403".
 */
class ExtensionSourceRateLimitException extends RuntimeException
{
    public function __construct(
        public readonly ?CarbonImmutable $resetsAt,
        public readonly bool $authenticated,
    ) {
        parent::__construct(self::buildMessage($resetsAt, $authenticated));
    }

    /**
     * Whether a GitHub API response is a rate-limit refusal.
     *
     * GitHub answers an exhausted limit with 403 (primary limit) or 429, and
     * always sets X-RateLimit-Remaining: 0. A plain 403 without that header
     * is a permission answer, not a limit.
     */
    public static function isRateLimited(Response $response): bool
    {
        return in_array($response->status(), [403, 429], true)
            && $response->header('X-RateLimit-Remaining') === '0';
    }

    public static function fromResponse(Response $response, bool $authenticated): self
    {
        $reset = $response->header('X-RateLimit-Reset');
        $resetsAt = ctype_digit($reset)
            ? CarbonImmutable::createFromTimestamp((int) $reset)
            : null;

        return new self($resetsAt, $authenticated);
    }

    /**
     * The reset time in the display timezone: H:i, plus the date when it is
     * not today there (a limit hit late at night resets after midnight).
     */
    private static function formatResetTime(CarbonImmutable $resetsAt): string
    {
        $timezone = self::displayTimezone();
        $local = $resetsAt->setTimezone($timezone);

        return $local->isSameDay(CarbonImmutable::now($timezone))
            ? $local->format('H:i')
            : $local->format('Y-m-d H:i');
    }

    /**
     * The timezone the admin panel shows times in. config('app.timezone') is
     * the storage timezone (UTC), so using it printed the reset time in UTC
     * on a site that displays JST.
     */
    private static function displayTimezone(): string
    {
        try {
            return \App\Helpers\DateTimeHelper::displayTimezone();
        } catch (\Throwable) {
            return (string) config('app.timezone', 'UTC');
        }
    }

    private static function buildMessage(?CarbonImmutable $resetsAt, bool $authenticated): string
    {
        $key = $authenticated
            ? 'services/extension_sources.rate_limited'
            : 'services/extension_sources.rate_limited_anonymous';

        $message = __($key);

        if ($resetsAt !== null) {
            $message .= ' '.__('services/extension_sources.rate_limit_resets_at', [
                'time' => self::formatResetTime($resetsAt),
            ]);
        }

        return $message;
    }
}
