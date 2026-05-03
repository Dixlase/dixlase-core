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

namespace App\Enums;

/**
 * ログレベル定義
 */
enum LogLevel: int
{
    case Emergency = 8;
    case Alert = 7;
    case Critical = 6;
    case Error = 5;
    case Warning = 4;
    case Notice = 3;
    case Info = 2;
    case Debug = 1;

    /**
     * Get the string representation of the log level
     */
    public function toString(): string
    {
        return match ($this) {
            self::Emergency => 'emergency',
            self::Alert => 'alert',
            self::Critical => 'critical',
            self::Error => 'error',
            self::Warning => 'warning',
            self::Notice => 'notice',
            self::Info => 'info',
            self::Debug => 'debug',
        };
    }

    /**
     * Get translation key for this log level
     */
    public function translationKey(): string
    {
        return $this->toString();
    }

    /**
     * Get all notification-worthy log levels (excluding debug, info, notice)
     */
    public static function getNotificationLevels(): array
    {
        return [
            self::Emergency->value,
            self::Alert->value,
            self::Critical->value,
            self::Error->value,
            self::Warning->value,
        ];
    }

    /**
     * Get default notification levels (most critical)
     */
    public static function getDefaultNotificationLevels(): array
    {
        return [
            self::Emergency->value,
            self::Alert->value,
            self::Critical->value,
            self::Error->value,
        ];
    }

    /**
     * Get all log levels as numeric values
     */
    public static function getAllLevels(): array
    {
        return array_map(fn ($case) => $case->value, self::cases());
    }

    /**
     * Get all log levels as string values
     */
    public static function getAllLevelStrings(): array
    {
        return array_map(fn ($case) => $case->toString(), self::cases());
    }

    /**
     * Get notification levels as string values
     */
    public static function getNotificationLevelStrings(): array
    {
        return [
            self::Emergency->toString(),
            self::Alert->toString(),
            self::Critical->toString(),
            self::Error->toString(),
            self::Warning->toString(),
        ];
    }

    /**
     * Get default notification levels as string values
     */
    public static function getDefaultNotificationLevelStrings(): array
    {
        return [
            self::Emergency->toString(),
            self::Alert->toString(),
            self::Critical->toString(),
            self::Error->toString(),
        ];
    }

    /**
     * Create LogLevel from string
     */
    public static function fromString(string $level): ?self
    {
        return match ($level) {
            'emergency' => self::Emergency,
            'alert' => self::Alert,
            'critical' => self::Critical,
            'error' => self::Error,
            'warning' => self::Warning,
            'notice' => self::Notice,
            'info' => self::Info,
            'debug' => self::Debug,
            default => null,
        };
    }

    /**
     * Get filter group for this log level
     * Groups: error (emergency, alert, critical, error), warning (warning, notice), normal (info), debug (debug)
     */
    public function getFilterGroup(): string
    {
        return match ($this) {
            self::Emergency, self::Alert, self::Critical, self::Error => 'error',
            self::Warning, self::Notice => 'warning',
            self::Info => 'normal',
            self::Debug => 'debug',
        };
    }

    /**
     * Get all filter groups with their log levels
     */
    public static function getFilterGroups(): array
    {
        return [
            'error' => [self::Emergency, self::Alert, self::Critical, self::Error],
            'warning' => [self::Warning, self::Notice],
            'normal' => [self::Info],
            'debug' => [self::Debug],
        ];
    }

    /**
     * Get log level strings for a filter group
     */
    public static function getLevelStringsForGroup(string $group): array
    {
        $groups = self::getFilterGroups();
        if (! isset($groups[$group])) {
            return [];
        }

        return array_map(fn ($level) => $level->toString(), $groups[$group]);
    }

    /**
     * Check if a log level string belongs to a filter group
     */
    public static function levelBelongsToGroup(string $levelString, string $group): bool
    {
        $levelStrings = self::getLevelStringsForGroup($group);

        return in_array(strtolower($levelString), $levelStrings);
    }
}
