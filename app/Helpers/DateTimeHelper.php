<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Helpers;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;
use Throwable;

/**
 * Helper that converts datetimes into the display timezone and formats them.
 *
 * Dixlase always stores and calculates in UTC (config('app.timezone')),
 * and converts to site_settings.display_timezone only for display. This helper
 * centralizes that display conversion and formatting. In Blade it is used as
 * the internal implementation of <x-ui-datetime>, so do not call directly from templates
 */
class DateTimeHelper
{
    /**
     * Named formats
     *
     * @var array<string, string>
     */
    public const FORMATS = [
        'date' => 'Y-m-d',
        'datetime' => 'Y-m-d H:i',
        'full' => 'Y-m-d H:i:s',
        'iso' => DateTimeInterface::ATOM,
    ];

    /**
     * Convert the given datetime into the display timezone and format it
     *
     * @param  Carbon|DateTimeInterface|string|int|null  $value  Input value (null/empty string returns null)
     * @param  string  $format  PHP date format string or a FORMATS key
     */
    public static function display(mixed $value, string $format = 'datetime'): ?string
    {
        $carbon = self::toCarbon($value);

        if ($carbon === null) {
            return null;
        }

        $resolvedFormat = self::FORMATS[$format] ?? $format;

        return $carbon->setTimezone(self::displayTimezone())->format($resolvedFormat);
    }

    /**
     * Convert the given datetime into a UTC ISO8601 string (for the HTML <time datetime> attribute)
     *
     * @param  Carbon|DateTimeInterface|string|int|null  $value
     */
    public static function toIsoUtc(mixed $value): ?string
    {
        $carbon = self::toCarbon($value);

        if ($carbon === null) {
            return null;
        }

        return $carbon->setTimezone('UTC')->format(DateTimeInterface::ATOM);
    }

    /**
     * Return the display timezone ID
     */
    public static function displayTimezone(): string
    {
        return ConfigHelper::getDisplayTimezone();
    }

    /**
     * Return a DateTimeZone object for the display timezone
     */
    public static function displayTimezoneObject(): DateTimeZone
    {
        return new DateTimeZone(self::displayTimezone());
    }

    /**
     * Normalize the input into a CarbonImmutable instance
     *
     * @param  Carbon|DateTimeInterface|string|int|null  $value
     */
    private static function toCarbon(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof CarbonImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        if (is_int($value)) {
            return CarbonImmutable::createFromTimestamp($value, 'UTC');
        }

        if (is_string($value)) {
            try {
                return CarbonImmutable::parse($value);
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }
}
