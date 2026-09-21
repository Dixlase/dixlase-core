<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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

namespace Tests\Unit;

use App\Enums\LogLevel;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class LogLevelEnumTest extends TestCase
{
    #[Group('enum')]
    #[Group('loglevel')]
    public function test_to_string_returns_correct_string_value(): void
    {
        $this->assertEquals('emergency', LogLevel::Emergency->toString());
        $this->assertEquals('alert', LogLevel::Alert->toString());
        $this->assertEquals('critical', LogLevel::Critical->toString());
        $this->assertEquals('error', LogLevel::Error->toString());
        $this->assertEquals('warning', LogLevel::Warning->toString());
        $this->assertEquals('notice', LogLevel::Notice->toString());
        $this->assertEquals('info', LogLevel::Info->toString());
        $this->assertEquals('debug', LogLevel::Debug->toString());
    }

    #[Group('enum')]
    #[Group('loglevel')]
    public function test_translation_key_returns_correct_key(): void
    {
        $this->assertEquals('emergency', LogLevel::Emergency->translationKey());
        $this->assertEquals('alert', LogLevel::Alert->translationKey());
    }

    #[Group('enum')]
    #[Group('loglevel')]
    public function test_from_string_returns_correct_enum_case(): void
    {
        $this->assertEquals(LogLevel::Emergency, LogLevel::fromString('emergency'));
        $this->assertEquals(LogLevel::Alert, LogLevel::fromString('alert'));
        $this->assertNull(LogLevel::fromString('ALERT')); // fromString() は小文字のみ受け付ける
        $this->assertNull(LogLevel::fromString('invalid_level'));
    }

    #[Group('enum')]
    #[Group('loglevel')]
    public function test_get_all_level_strings_returns_all_levels(): void
    {
        $expected = [
            'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug',
        ];
        $this->assertEquals($expected, LogLevel::getAllLevelStrings());
    }

    #[Group('enum')]
    #[Group('loglevel')]
    public function test_get_notification_levels_returns_correct_levels(): void
    {
        $expected = [
            LogLevel::Emergency->value,
            LogLevel::Alert->value,
            LogLevel::Critical->value,
            LogLevel::Error->value,
            LogLevel::Warning->value,
        ];
        $this->assertEquals($expected, LogLevel::getNotificationLevels());
    }

    #[Group('enum')]
    #[Group('loglevel')]
    public function test_get_default_notification_levels_returns_correct_levels(): void
    {
        $expected = [
            LogLevel::Emergency->value,
            LogLevel::Alert->value,
            LogLevel::Critical->value,
            LogLevel::Error->value,
        ];
        $this->assertEquals($expected, LogLevel::getDefaultNotificationLevels());
    }

    #[Group('enum')]
    #[Group('loglevel')]
    public function test_get_notification_level_strings_returns_correct_strings(): void
    {
        $expected = [
            'emergency', 'alert', 'critical', 'error', 'warning',
        ];
        $this->assertEquals($expected, LogLevel::getNotificationLevelStrings());
    }

    #[Group('enum')]
    #[Group('loglevel')]
    public function test_get_default_notification_level_strings_returns_correct_strings(): void
    {
        $expected = [
            'emergency', 'alert', 'critical', 'error',
        ];
        $this->assertEquals($expected, LogLevel::getDefaultNotificationLevelStrings());
    }
}
