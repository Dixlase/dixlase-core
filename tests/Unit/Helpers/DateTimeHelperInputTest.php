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
 */

namespace Tests\Unit\Helpers;

use App\Contracts\ViewerTimezoneResolver;
use App\Helpers\DateTimeHelper;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

/**
 * The input side of the UTC / display-timezone split.
 *
 * An <input type="datetime-local"> submits a wall clock with no offset, so
 * it means nothing until a timezone is chosen for it. forInput() and
 * fromInput() are the two halves, and they must resolve that timezone the
 * same way: a field shown in one timezone and saved as another stops
 * round-tripping, and the failure is silent, because the form reads back
 * the value it wrote and only the rendered page disagrees.
 */
class DateTimeHelperInputTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // ConfigHelper::getFromDatabase only reads the database when
        // INSTALLED is true, and phpunit.xml sets it false through
        // $_ENV/$_SERVER, so all three channels are overridden.
        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';
    }

    protected function tearDown(): void
    {
        SiteSetting::query()->where('name', 'display_timezone')->delete();
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';

        parent::tearDown();
    }

    public function test_a_stored_utc_value_renders_as_a_local_wall_clock(): void
    {
        $this->setDisplayTimezone('Asia/Tokyo');

        $this->assertSame('2026-10-26T10:00', DateTimeHelper::forInput('2026-10-26 01:00:00'));
    }

    public function test_a_submitted_wall_clock_is_read_in_the_display_timezone(): void
    {
        $this->setDisplayTimezone('Asia/Tokyo');

        $this->assertSame('2026-10-26 01:00:00', DateTimeHelper::fromInput('2026-10-26T10:00'));
        $this->assertSame('2026-10-26 01:00:00', DateTimeHelper::fromInput('2026-10-26 10:00:00'));
    }

    public function test_the_two_halves_round_trip(): void
    {
        $this->setDisplayTimezone('Asia/Tokyo');

        foreach (['2026-01-01T00:00', '2026-06-30T23:59', '2026-10-26T10:00'] as $typed) {
            $this->assertSame($typed, DateTimeHelper::forInput(DateTimeHelper::fromInput($typed)));
        }
    }

    public function test_an_explicit_offset_in_the_submitted_value_wins(): void
    {
        $this->setDisplayTimezone('Asia/Tokyo');

        // Already absolute, so it must not be shifted again.
        $this->assertSame('2026-10-26 01:00:00', DateTimeHelper::fromInput('2026-10-26T01:00:00Z'));
        $this->assertSame('2026-10-26 01:00:00', DateTimeHelper::fromInput('2026-10-26T10:00:00+09:00'));
    }

    public function test_an_empty_or_unparseable_value_is_null(): void
    {
        $this->setDisplayTimezone('Asia/Tokyo');

        foreach (['', '   ', null, 'not a date', 42] as $value) {
            $this->assertNull(DateTimeHelper::fromInput($value));
        }

        $this->assertNull(DateTimeHelper::forInput(null));
        $this->assertNull(DateTimeHelper::forInput(''));
    }

    public function test_an_explicit_timezone_wins_over_the_site_setting(): void
    {
        $this->setDisplayTimezone('Asia/Tokyo');

        $this->assertSame('2026-10-26T02:00', DateTimeHelper::forInput('2026-10-26 01:00:00', 'Europe/Paris'));
        $this->assertSame('2026-10-26 01:00:00', DateTimeHelper::fromInput('2026-10-26T02:00', 'Europe/Paris'));
    }

    public function test_an_unknown_timezone_falls_back_to_the_site_setting(): void
    {
        $this->setDisplayTimezone('Asia/Tokyo');

        // A stored identifier can go stale; a date on a screen is not worth
        // a 500, so the site's clock is used instead.
        $this->assertSame('2026-10-26T10:00', DateTimeHelper::forInput('2026-10-26 01:00:00', 'Mars/Olympus_Mons'));
    }

    public function test_a_daylight_saving_zone_uses_the_offset_in_force_on_the_day(): void
    {
        $this->setDisplayTimezone('America/New_York');

        // The same wall clock six months apart: EST is -05:00, EDT -04:00.
        // A fixed offset would make these two equal.
        $this->assertSame('2026-01-15 15:00:00', DateTimeHelper::fromInput('2026-01-15T10:00'));
        $this->assertSame('2026-07-15 14:00:00', DateTimeHelper::fromInput('2026-07-15T10:00'));
    }

    public function test_without_a_resolver_the_viewer_timezone_is_the_sites(): void
    {
        $this->setDisplayTimezone('Asia/Tokyo');

        $this->assertFalse($this->app->bound(ViewerTimezoneResolver::class));
        $this->assertSame('Asia/Tokyo', DateTimeHelper::viewerTimezone());
        $this->assertSame('Asia/Tokyo', DateTimeHelper::fieldTimezone(null, 'viewer'));
    }

    public function test_a_registered_resolver_gives_the_viewer_their_own_timezone(): void
    {
        $this->setDisplayTimezone('Asia/Tokyo');
        $this->bindResolver(fn () => 'Europe/Paris');

        $this->assertSame('Europe/Paris', DateTimeHelper::viewerTimezone());
        $this->assertSame('Europe/Paris', DateTimeHelper::fieldTimezone(null, 'viewer'));

        // The site's clock still governs a field that did not ask for the
        // viewer's, so an admin screen is unaffected by the extension.
        $this->assertSame('Asia/Tokyo', DateTimeHelper::fieldTimezone(null, 'site'));
    }

    public function test_a_resolver_returning_nothing_falls_back_to_the_site(): void
    {
        $this->setDisplayTimezone('Asia/Tokyo');
        $this->bindResolver(fn () => null);

        $this->assertSame('Asia/Tokyo', DateTimeHelper::viewerTimezone());
    }

    public function test_a_resolver_naming_an_unknown_timezone_falls_back_to_the_site(): void
    {
        $this->setDisplayTimezone('Asia/Tokyo');
        $this->bindResolver(fn () => 'Mars/Olympus_Mons');

        $this->assertSame('Asia/Tokyo', DateTimeHelper::viewerTimezone());
    }

    public function test_a_throwing_resolver_does_not_break_the_page(): void
    {
        $this->setDisplayTimezone('Asia/Tokyo');
        $this->bindResolver(function () {
            throw new RuntimeException('the profile store is down');
        });

        $this->assertSame('Asia/Tokyo', DateTimeHelper::viewerTimezone());
    }

    private function setDisplayTimezone(string $tz): void
    {
        SiteSetting::setValue('display_timezone', $tz);
    }

    /**
     * @param  \Closure():(string|null)  $resolve
     */
    private function bindResolver(\Closure $resolve): void
    {
        $this->app->singleton(ViewerTimezoneResolver::class, fn () => new class($resolve) implements ViewerTimezoneResolver
        {
            public function __construct(private \Closure $resolve) {}

            public function resolve(): ?string
            {
                return ($this->resolve)();
            }
        });
    }
}
