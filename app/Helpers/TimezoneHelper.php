<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

use DateTime;
use DateTimeZone;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 */
class TimezoneHelper
{
    public static function getTimezonesWithUtcOffset(): array
    {
        $timezones = [];
        $now = new DateTime('now');
        $translations = trans('timezones'); // resources/lang/ja/timezones.php

        foreach (DateTimeZone::listIdentifiers() as $timezone) {
            $tz = new DateTimeZone($timezone);
            $offset = $tz->getOffset($now);
            $sign = $offset < 0 ? '-' : '+';
            $hours = str_pad(floor(abs($offset) / 3600), 2, '0', STR_PAD_LEFT);
            $minutes = str_pad(floor(abs($offset) % 3600 / 60), 2, '0', STR_PAD_LEFT);
            $formattedOffset = "UTC{$sign}{$hours}:{$minutes}";

            $label = $translations[$timezone] ?? $timezone;
            $timezones[$timezone] = "（{$formattedOffset}）{$label}";
        }

        return $timezones;
    }
}
