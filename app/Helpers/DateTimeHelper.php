<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;
use Throwable;

/**
 * 日時を表示用タイムゾーンに変換してフォーマットするヘルパー。
 *
 * Dixlase は保存・計算を常に UTC（config('app.timezone')）で行い、
 * 表示時にのみ base_settings.display_timezone へ変換する。本ヘルパーは
 * その表示変換とフォーマットを集約する。Blade では <x-ui-datetime> の
 * 内部実装として使われるため、テンプレートから直接呼ばないこと。
 */
class DateTimeHelper
{
    /**
     * 名前付きフォーマット
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
     * 入力日時を表示用タイムゾーンに変換してフォーマットする
     *
     * @param  Carbon|DateTimeInterface|string|int|null  $value  入力値（null/空文字なら null を返す）
     * @param  string  $format  PHP date format 文字列、または FORMATS のキー
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
     * 入力日時を UTC の ISO8601 文字列に変換する（HTML <time datetime> 用）
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
     * 表示用タイムゾーン ID を返す
     */
    public static function displayTimezone(): string
    {
        return ConfigHelper::getDisplayTimezone();
    }

    /**
     * 表示用 DateTimeZone オブジェクトを返す
     */
    public static function displayTimezoneObject(): DateTimeZone
    {
        return new DateTimeZone(self::displayTimezone());
    }

    /**
     * 入力を CarbonImmutable に正規化する
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
