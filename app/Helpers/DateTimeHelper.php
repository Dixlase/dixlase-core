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

use App\Contracts\ViewerTimezoneResolver;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
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
     * @param  CarbonImmutable|DateTimeInterface|string|int|null  $value  Input value (null/empty string returns null)
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
     * @param  CarbonImmutable|DateTimeInterface|string|int|null  $value
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
     * Return the timezone to show the current viewer
     *
     * The site's display timezone is the right clock for an admin screen,
     * but not for one that shows a member their own data. An extension that
     * owns a profile screen registers a ViewerTimezoneResolver; with none
     * registered this is the display timezone, so nothing changes for a
     * site that has no such extension.
     *
     * A resolver that throws, or names a timezone PHP does not know, is
     * ignored rather than allowed to break the page: a stored identifier
     * can go stale, and a date on a screen is not worth a 500.
     */
    public static function viewerTimezone(): string
    {
        $fallback = self::displayTimezone();

        if (! App::bound(ViewerTimezoneResolver::class)) {
            return $fallback;
        }

        try {
            $resolved = App::make(ViewerTimezoneResolver::class)->resolve();
        } catch (Throwable $e) {
            Log::warning('ViewerTimezoneResolver failed; using the display timezone', ['exception' => $e]);

            return $fallback;
        }

        return self::isKnownTimezone($resolved) ? (string) $resolved : $fallback;
    }

    /**
     * Resolve the timezone a datetime field is shown and read in
     *
     * @param  string|null  $timezone  An explicit identifier, which wins when given
     * @param  string  $context  'site' for the site's display timezone, 'viewer' for the member's own
     */
    public static function fieldTimezone(?string $timezone = null, string $context = 'site'): string
    {
        if (self::isKnownTimezone($timezone)) {
            return (string) $timezone;
        }

        return $context === 'viewer' ? self::viewerTimezone() : self::displayTimezone();
    }

    /**
     * Render a stored UTC value as the wall clock a datetime-local field wants
     *
     * An <input type="datetime-local"> submits a wall clock with no offset,
     * so it has to be given one deliberately in both directions. This is
     * the display half; fromInput() is the other.
     *
     * Accepts what old() returns as readily as a model attribute, so a form
     * redisplayed after a failed validation needs no second branch.
     *
     * @param  CarbonImmutable|DateTimeInterface|string|int|null  $value  Interpreted as UTC
     * @param  string|null  $timezone  Defaults to the site's display timezone
     */
    public static function forInput(mixed $value, ?string $timezone = null): ?string
    {
        $carbon = self::toCarbon($value);

        if ($carbon === null) {
            return null;
        }

        return $carbon->setTimezone(self::fieldTimezone($timezone))->format('Y-m-d\TH:i');
    }

    /**
     * Read a submitted wall clock in the field's timezone and return UTC
     *
     * The inverse of forInput(). Both must resolve the timezone the same
     * way: a field shown in one timezone and saved as another stops
     * round-tripping, and that failure is silent, because the form reads
     * back the value it wrote and only the rendered page disagrees.
     *
     * Returns null both for an empty value and for one that cannot be
     * parsed. A caller normalising submitted input must tell the two apart
     * and leave an unparseable value exactly as it arrived, so its `date`
     * rule reports it instead of `nullable` dropping it in silence.
     *
     * @param  string|null  $timezone  Defaults to the site's display timezone
     */
    public static function fromInput(mixed $value, ?string $timezone = null): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            // An explicit offset in the submitted value wins: Carbon honours
            // it and ignores the second argument. Only a bare wall clock,
            // which is what datetime-local sends, is read as field-local.
            return CarbonImmutable::parse($value, self::fieldTimezone($timezone))
                ->setTimezone((string) config('app.timezone', 'UTC'))
                ->format('Y-m-d H:i:s');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Whether PHP knows this timezone identifier
     */
    private static function isKnownTimezone(mixed $timezone): bool
    {
        if (! is_string($timezone) || $timezone === '') {
            return false;
        }

        try {
            new DateTimeZone($timezone);
        } catch (Throwable) {
            return false;
        }

        return true;
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
     * @param  CarbonImmutable|DateTimeInterface|string|int|null  $value
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
