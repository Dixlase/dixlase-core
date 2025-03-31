<?php

namespace App\Helpers;

use DateTime;
use DateTimeZone;

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
