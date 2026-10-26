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

namespace Tests\Unit\View;

use App\Contracts\ViewerTimezoneResolver;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * <x-form-datetime> is the input-side counterpart of <x-ui-datetime>. It
 * renders a stored UTC value as the wall clock a datetime-local field
 * wants, in a timezone it resolves rather than assumes, and says which
 * timezone that is — the field submits no offset, so which clock it means
 * is part of the field, not a detail.
 */
class FormDatetimeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // ConfigHelper reads the database for display_timezone only when
        // INSTALLED is true; phpunit.xml sets it false through
        // $_ENV/$_SERVER, so all three channels are overridden.
        putenv('INSTALLED=true');
        $_ENV['INSTALLED'] = 'true';
        $_SERVER['INSTALLED'] = 'true';

        SiteSetting::setValue('display_timezone', 'Asia/Tokyo');
    }

    protected function tearDown(): void
    {
        SiteSetting::query()->where('name', 'display_timezone')->delete();
        putenv('INSTALLED=false');
        $_ENV['INSTALLED'] = 'false';
        $_SERVER['INSTALLED'] = 'false';

        parent::tearDown();
    }

    public function test_it_renders_a_stored_utc_value_as_a_local_wall_clock(): void
    {
        $html = Blade::render('<x-form-datetime name="starts_at" value="2026-10-26 01:00:00" />');

        $this->assertStringContainsString('type="datetime-local"', $html);
        $this->assertStringContainsString('value="2026-10-26T10:00"', $html);
        $this->assertStringNotContainsString('value="2026-10-26T01:00"', $html);
    }

    public function test_it_names_the_timezone_and_points_the_field_at_it(): void
    {
        $html = Blade::render('<x-form-datetime name="starts_at" value="2026-10-26 01:00:00" />');

        $this->assertStringContainsString('Asia/Tokyo', $html);
        $this->assertStringContainsString('id="starts_at_timezone"', $html);
        $this->assertStringContainsString('aria-describedby="starts_at_timezone"', $html);
    }

    public function test_the_timezone_note_can_be_turned_off(): void
    {
        $html = Blade::render('<x-form-datetime name="starts_at" :show-timezone="false" />');

        $this->assertStringNotContainsString('Asia/Tokyo', $html);
        $this->assertStringNotContainsString('starts_at_timezone', $html);
    }

    public function test_an_empty_value_renders_an_empty_field(): void
    {
        $html = Blade::render('<x-form-datetime name="starts_at" :value="null" />');

        $this->assertStringContainsString('type="datetime-local"', $html);
        $this->assertStringNotContainsString('value="2026', $html);
    }

    public function test_the_bounds_are_converted_too(): void
    {
        // A datetime-local field compares min / max against wall clocks in
        // its own timezone, so a UTC bound left alone refuses the wrong
        // values.
        $html = Blade::render('<x-form-datetime name="starts_at" min="2026-10-26 01:00:00" max="2026-10-27 01:00:00" />');

        $this->assertStringContainsString('min="2026-10-26T10:00"', $html);
        $this->assertStringContainsString('max="2026-10-27T10:00"', $html);
    }

    public function test_an_explicit_timezone_wins(): void
    {
        $html = Blade::render('<x-form-datetime name="starts_at" value="2026-10-26 01:00:00" timezone="Europe/Paris" />');

        $this->assertStringContainsString('value="2026-10-26T02:00"', $html);
        $this->assertStringContainsString('Europe/Paris', $html);
    }

    public function test_the_viewer_context_uses_the_registered_resolver(): void
    {
        $this->app->singleton(ViewerTimezoneResolver::class, fn () => new class implements ViewerTimezoneResolver
        {
            public function resolve(): ?string
            {
                return 'Europe/Paris';
            }
        });

        $viewer = Blade::render('<x-form-datetime name="starts_at" value="2026-10-26 01:00:00" context="viewer" />');
        $this->assertStringContainsString('value="2026-10-26T02:00"', $viewer);

        // The site's clock still governs a field that did not ask for the
        // viewer's, so an admin screen is unaffected.
        $site = Blade::render('<x-form-datetime name="starts_at" value="2026-10-26 01:00:00" />');
        $this->assertStringContainsString('value="2026-10-26T10:00"', $site);
    }

    public function test_the_viewer_context_without_a_resolver_matches_the_site(): void
    {
        $this->assertFalse($this->app->bound(ViewerTimezoneResolver::class));

        $html = Blade::render('<x-form-datetime name="starts_at" value="2026-10-26 01:00:00" context="viewer" />');

        $this->assertStringContainsString('value="2026-10-26T10:00"', $html);
        $this->assertStringContainsString('Asia/Tokyo', $html);
    }

    public function test_extra_attributes_reach_the_input(): void
    {
        $html = Blade::render('<x-form-datetime name="starts_at" x-bind:disabled="off" class="input-full" />');

        $this->assertStringContainsString('x-bind:disabled="off"', $html);
        $this->assertStringContainsString('input-full', $html);
    }
}
