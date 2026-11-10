<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
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

namespace Tests\Unit\Helpers;

use App\Contracts\Demo\DemoExpiryProvider;
use App\Helpers\DemoModeHelper;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use RuntimeException;
use Tests\TestCase;

/**
 * DemoModeHelper::expiresAt() asks a registered DemoExpiryProvider, falls back
 * to the legacy `dixlase.demo_expires_at` config while none is registered, and
 * answers null (no expiry line) when neither supplies one.
 */
class DemoModeHelperTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['dixlase.demo_expires_at' => null]);
    }

    public function test_returns_null_with_no_provider_and_no_config(): void
    {
        $this->assertNull(DemoModeHelper::expiresAt());
    }

    public function test_falls_back_to_the_legacy_config_when_no_provider_is_registered(): void
    {
        config(['dixlase.demo_expires_at' => '2026-11-10T21:00:00+00:00']);

        $expires = DemoModeHelper::expiresAt();

        $this->assertInstanceOf(CarbonInterface::class, $expires);
        $this->assertSame('2026-11-10T21:00:00+00:00', $expires->toIso8601String());
    }

    public function test_a_registered_provider_is_used_and_the_config_is_ignored(): void
    {
        config(['dixlase.demo_expires_at' => '2026-01-01T00:00:00+00:00']);
        $providerValue = Carbon::parse('2026-12-31T23:59:00+00:00');
        $this->app->instance(DemoExpiryProvider::class, $this->makeProvider($providerValue));

        $this->assertSame('2026-12-31T23:59:00+00:00', DemoModeHelper::expiresAt()?->toIso8601String());
    }

    public function test_a_provider_returning_null_hides_the_expiry_even_with_a_config(): void
    {
        config(['dixlase.demo_expires_at' => '2026-01-01T00:00:00+00:00']);
        $this->app->instance(DemoExpiryProvider::class, $this->makeProvider(null));

        $this->assertNull(DemoModeHelper::expiresAt());
    }

    public function test_a_throwing_provider_is_contained_and_hides_the_expiry(): void
    {
        $this->app->instance(DemoExpiryProvider::class, new class implements DemoExpiryProvider
        {
            public function expiresAt(): ?CarbonInterface
            {
                throw new RuntimeException('boom');
            }
        });

        $this->assertNull(DemoModeHelper::expiresAt());
    }

    private function makeProvider(?CarbonInterface $value): DemoExpiryProvider
    {
        return new class($value) implements DemoExpiryProvider
        {
            public function __construct(private ?CarbonInterface $value) {}

            public function expiresAt(): ?CarbonInterface
            {
                return $this->value;
            }
        };
    }
}
