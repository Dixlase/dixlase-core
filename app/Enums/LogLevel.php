<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Enums;

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
        return array_map(fn($case) => $case->value, self::cases());
    }

    /**
     * Get all log levels as string values
     */
    public static function getAllLevelStrings(): array
    {
        return array_map(fn($case) => $case->toString(), self::cases());
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
}
