<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Enums\LogLevel;

class LogLevelTest extends TestCase
{
    /**
     * Test that LogLevel enum has correct integer values
     */
    public function test_enum_has_correct_integer_values()
    {
        $this->assertEquals(8, LogLevel::Emergency->value);
        $this->assertEquals(7, LogLevel::Alert->value);
        $this->assertEquals(6, LogLevel::Critical->value);
        $this->assertEquals(5, LogLevel::Error->value);
        $this->assertEquals(4, LogLevel::Warning->value);
        $this->assertEquals(3, LogLevel::Notice->value);
        $this->assertEquals(2, LogLevel::Info->value);
        $this->assertEquals(1, LogLevel::Debug->value);
    }

    /**
     * Test toString method returns correct string representations
     */
    public function test_to_string_method()
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

    /**
     * Test translationKey method returns correct keys
     */
    public function test_translation_key_method()
    {
        $this->assertEquals('emergency', LogLevel::Emergency->translationKey());
        $this->assertEquals('error', LogLevel::Error->translationKey());
        $this->assertEquals('warning', LogLevel::Warning->translationKey());
        $this->assertEquals('debug', LogLevel::Debug->translationKey());
    }

    /**
     * Test fromString method creates correct enum instances
     */
    public function test_from_string_method()
    {
        $this->assertEquals(LogLevel::Emergency, LogLevel::fromString('emergency'));
        $this->assertEquals(LogLevel::Alert, LogLevel::fromString('alert'));
        $this->assertEquals(LogLevel::Critical, LogLevel::fromString('critical'));
        $this->assertEquals(LogLevel::Error, LogLevel::fromString('error'));
        $this->assertEquals(LogLevel::Warning, LogLevel::fromString('warning'));
        $this->assertEquals(LogLevel::Notice, LogLevel::fromString('notice'));
        $this->assertEquals(LogLevel::Info, LogLevel::fromString('info'));
        $this->assertEquals(LogLevel::Debug, LogLevel::fromString('debug'));
        
        // Test invalid string returns null
        $this->assertNull(LogLevel::fromString('invalid'));
        $this->assertNull(LogLevel::fromString(''));
    }

    /**
     * Test getNotificationLevels returns correct levels
     */
    public function test_get_notification_levels()
    {
        $expected = [8, 7, 6, 5, 4]; // Emergency, Alert, Critical, Error, Warning
        $actual = LogLevel::getNotificationLevels();
        
        $this->assertEquals($expected, $actual);
        $this->assertCount(5, $actual);
    }

    /**
     * Test getDefaultNotificationLevels returns correct levels
     */
    public function test_get_default_notification_levels()
    {
        $expected = [8, 7, 6, 5]; // Emergency, Alert, Critical, Error
        $actual = LogLevel::getDefaultNotificationLevels();
        
        $this->assertEquals($expected, $actual);
        $this->assertCount(4, $actual);
    }

    /**
     * Test getAllLevels returns all enum values
     */
    public function test_get_all_levels()
    {
        $expected = [8, 7, 6, 5, 4, 3, 2, 1]; // All levels
        $actual = LogLevel::getAllLevels();
        
        $this->assertEquals($expected, $actual);
        $this->assertCount(8, $actual);
    }

    /**
     * Test getAllLevelStrings returns all string representations
     */
    public function test_get_all_level_strings()
    {
        $expected = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];
        $actual = LogLevel::getAllLevelStrings();
        
        $this->assertEquals($expected, $actual);
        $this->assertCount(8, $actual);
    }

    /**
     * Test getNotificationLevelStrings returns correct string representations
     */
    public function test_get_notification_level_strings()
    {
        $expected = ['emergency', 'alert', 'critical', 'error', 'warning'];
        $actual = LogLevel::getNotificationLevelStrings();
        
        $this->assertEquals($expected, $actual);
        $this->assertCount(5, $actual);
    }

    /**
     * Test getDefaultNotificationLevelStrings returns correct string representations
     */
    public function test_get_default_notification_level_strings()
    {
        $expected = ['emergency', 'alert', 'critical', 'error'];
        $actual = LogLevel::getDefaultNotificationLevelStrings();
        
        $this->assertEquals($expected, $actual);
        $this->assertCount(4, $actual);
    }

    /**
     * Test JSON encoding for database storage
     */
    public function test_json_encoding_for_database()
    {
        $defaultLevels = LogLevel::getDefaultNotificationLevels();
        $json = json_encode($defaultLevels);
        $decoded = json_decode($json, true);
        
        $this->assertEquals('[8,7,6,5]', $json);
        $this->assertEquals($defaultLevels, $decoded);
        $this->assertTrue(is_array($decoded));
        
        // Test all notification levels
        $notificationLevels = LogLevel::getNotificationLevels();
        $json = json_encode($notificationLevels);
        $this->assertEquals('[8,7,6,5,4]', $json);
    }

    /**
     * Test enum creation from integer values
     */
    public function test_enum_creation_from_integer()
    {
        $this->assertEquals(LogLevel::Emergency, LogLevel::from(8));
        $this->assertEquals(LogLevel::Alert, LogLevel::from(7));
        $this->assertEquals(LogLevel::Critical, LogLevel::from(6));
        $this->assertEquals(LogLevel::Error, LogLevel::from(5));
        $this->assertEquals(LogLevel::Warning, LogLevel::from(4));
        $this->assertEquals(LogLevel::Notice, LogLevel::from(3));
        $this->assertEquals(LogLevel::Info, LogLevel::from(2));
        $this->assertEquals(LogLevel::Debug, LogLevel::from(1));
    }

    /**
     * Test that notification levels exclude debug, info, and notice
     */
    public function test_notification_levels_exclude_low_priority()
    {
        $notificationLevels = LogLevel::getNotificationLevels();
        
        // Should include these
        $this->assertContains(LogLevel::Emergency->value, $notificationLevels);
        $this->assertContains(LogLevel::Alert->value, $notificationLevels);
        $this->assertContains(LogLevel::Critical->value, $notificationLevels);
        $this->assertContains(LogLevel::Error->value, $notificationLevels);
        $this->assertContains(LogLevel::Warning->value, $notificationLevels);
        
        // Should exclude these
        $this->assertNotContains(LogLevel::Notice->value, $notificationLevels);
        $this->assertNotContains(LogLevel::Info->value, $notificationLevels);
        $this->assertNotContains(LogLevel::Debug->value, $notificationLevels);
    }

    /**
     * Test that default notification levels exclude warning and lower
     */
    public function test_default_notification_levels_exclude_warning_and_lower()
    {
        $defaultLevels = LogLevel::getDefaultNotificationLevels();
        
        // Should include these
        $this->assertContains(LogLevel::Emergency->value, $defaultLevels);
        $this->assertContains(LogLevel::Alert->value, $defaultLevels);
        $this->assertContains(LogLevel::Critical->value, $defaultLevels);
        $this->assertContains(LogLevel::Error->value, $defaultLevels);
        
        // Should exclude these
        $this->assertNotContains(LogLevel::Warning->value, $defaultLevels);
        $this->assertNotContains(LogLevel::Notice->value, $defaultLevels);
        $this->assertNotContains(LogLevel::Info->value, $defaultLevels);
        $this->assertNotContains(LogLevel::Debug->value, $defaultLevels);
    }
}
