<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace Tests\Unit\Helpers;

use App\Contracts\I18n\LocaleUrlRouting;
use App\Helpers\LocaleHelper;
use Tests\TestCase;

/**
 * Plugins ask LocaleHelper whether front URLs carry a locale segment;
 * the answer comes from whatever binds LocaleUrlRouting, and is false
 * when nothing does.
 */
class LocaleUrlRoutingTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->app->offsetUnset(LocaleUrlRouting::class);
        parent::tearDown();
    }

    public function test_is_false_when_no_locale_router_is_bound(): void
    {
        $this->app->offsetUnset(LocaleUrlRouting::class);

        $this->assertFalse(LocaleHelper::localeUrlRoutingEnabled());
    }

    public function test_follows_the_bound_locale_router(): void
    {
        $this->bindRouting(true);
        $this->assertTrue(LocaleHelper::localeUrlRoutingEnabled());

        $this->bindRouting(false);
        $this->assertFalse(LocaleHelper::localeUrlRoutingEnabled());
    }

    public function test_ignores_a_binding_that_does_not_implement_the_contract(): void
    {
        $this->app->instance(LocaleUrlRouting::class, new \stdClass());

        $this->assertFalse(LocaleHelper::localeUrlRoutingEnabled());
    }

    private function bindRouting(bool $enabled): void
    {
        $this->app->instance(LocaleUrlRouting::class, new class($enabled) implements LocaleUrlRouting
        {
            public function __construct(private bool $enabled) {}

            public function isEnabled(): bool
            {
                return $this->enabled;
            }
        });
    }
}
