<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Enums\LogLevel;

class LogLevelEnumTest extends TestCase
{
    /**
     * @test
     * @group enum
     * @group loglevel
     */
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

    /**
     * @test
     * @group enum
     * @group loglevel
     */
    public function test_translation_key_returns_correct_key(): void
    {
        $this->assertEquals('admin.log_level.emergency', LogLevel::Emergency->translationKey());
        $this->assertEquals('admin.log_level.alert', LogLevel::Alert->translationKey());
    }

    /**
     * @test
     * @group enum
     * @group loglevel
     */
    public function test_from_string_returns_correct_enum_case(): void
    {
        $this->assertEquals(LogLevel::Emergency, LogLevel::fromString('emergency'));
        $this->assertEquals(LogLevel::Alert, LogLevel::fromString('ALERT')); // test case-insensitivity
        $this->assertNull(LogLevel::fromString('invalid_level'));
    }

    /**
     * @test
     * @group enum
     * @group loglevel
     */
    public function test_get_all_level_strings_returns_all_levels(): void
    {
        $expected = [
            'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'
        ];
        $this->assertEquals($expected, LogLevel::getAllLevelStrings());
    }

    /**
     * @test
     * @group enum
     * @group loglevel
     */
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

    /**
     * @test
     * @group enum
     * @group loglevel
     */
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

    /**
     * @test
     * @group enum
     * @group loglevel
     */
    public function test_get_notification_level_strings_returns_correct_strings(): void
    {
        $expected = [
            'emergency', 'alert', 'critical', 'error', 'warning'
        ];
        $this->assertEquals($expected, LogLevel::getNotificationLevelStrings());
    }

    /**
     * @test
     * @group enum
     * @group loglevel
     */
    public function test_get_default_notification_level_strings_returns_correct_strings(): void
    {
        $expected = [
            'emergency', 'alert', 'critical', 'error'
        ];
        $this->assertEquals($expected, LogLevel::getDefaultNotificationLevelStrings());
    }
}
