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

namespace App\Enums;

/**
 * Gender Enum
 *
 * Enum representing gender. Stored as numeric values in the database.
 * Uses translation keys common.gender_*
 */
enum Gender: int
{
    case MALE = 1;
    case FEMALE = 2;
    case NON_BINARY = 3;
    case OTHER = 4;
    case PREFER_NOT_TO_SAY = 9;

    /**
     * Get translation key
     */
    public function label(): string
    {
        return match ($this) {
            self::MALE => __('common.gender_male'),
            self::FEMALE => __('common.gender_female'),
            self::NON_BINARY => __('common.gender_non_binary'),
            self::OTHER => __('common.gender_other'),
            self::PREFER_NOT_TO_SAY => __('common.prefer_not_to_say'),
        };
    }

    /**
     * Get translation key string (without __())
     */
    public function translationKey(): string
    {
        return match ($this) {
            self::MALE => 'common.gender_male',
            self::FEMALE => 'common.gender_female',
            self::NON_BINARY => 'common.gender_non_binary',
            self::OTHER => 'common.gender_other',
            self::PREFER_NOT_TO_SAY => 'common.prefer_not_to_say',
        };
    }

    /**
     * Get name from value (for debugging)
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get all options as array (for select box)
     *
     * @return array<int, string>
     */
    public static function options(): array
    {
        return [
            self::MALE->value => self::MALE->label(),
            self::FEMALE->value => self::FEMALE->label(),
            self::NON_BINARY->value => self::NON_BINARY->label(),
            self::OTHER->value => self::OTHER->label(),
            self::PREFER_NOT_TO_SAY->value => self::PREFER_NOT_TO_SAY->label(),
        ];
    }

    /**
     * Get basic options only (male and female only)
     *
     * @return array<int, string>
     */
    public static function basicOptions(): array
    {
        return [
            self::MALE->value => self::MALE->label(),
            self::FEMALE->value => self::FEMALE->label(),
        ];
    }

    /**
     * Get extended options (male, female, other, prefer not to answer)
     *
     * @return array<int, string>
     */
    public static function extendedOptions(): array
    {
        return [
            self::MALE->value => self::MALE->label(),
            self::FEMALE->value => self::FEMALE->label(),
            self::OTHER->value => self::OTHER->label(),
            self::PREFER_NOT_TO_SAY->value => self::PREFER_NOT_TO_SAY->label(),
        ];
    }

    /**
     * Get corresponding Enum case from value (null-safe)
     */
    public static function fromValue(?int $value): ?self
    {
        if ($value === null) {
            return null;
        }

        return self::tryFrom($value);
    }

    /**
     * Get Enum case from string name
     */
    public static function fromName(string $name): ?self
    {
        return match (strtoupper($name)) {
            'MALE' => self::MALE,
            'FEMALE' => self::FEMALE,
            'NON_BINARY', 'NONBINARY' => self::NON_BINARY,
            'OTHER' => self::OTHER,
            'PREFER_NOT_TO_SAY', 'PREFERNOTTOSAY' => self::PREFER_NOT_TO_SAY,
            default => null,
        };
    }

    /**
     * Get all cases as array
     *
     * @return array<self>
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * Get array of values
     *
     * @return array<int>
     */
    public static function values(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }
}
