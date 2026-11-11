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

use App\Captcha\CaptchaDriver;
use App\Captcha\CaptchaResult;
use App\Captcha\ExpectsAction;
use App\Helpers\CaptchaHelper;
use Illuminate\Http\Request;
use RuntimeException;
use Tests\TestCase;

/**
 * CaptchaHelper::verifyGlobally() verifies a token against the globally
 * configured CAPTCHA for a non-form endpoint, without the per-form gate, and
 * fails closed. It lets an extension's own endpoint protect itself through the
 * stable API rather than resolving the concrete CaptchaDriver.
 */
class CaptchaHelperVerifyGloballyTest extends TestCase
{
    public function test_it_returns_true_when_the_driver_verifies_the_token(): void
    {
        // No per-form setting is configured: verifyGlobally does not gate on it.
        $this->app->instance(CaptchaDriver::class, $this->driver(true));

        $this->assertTrue(CaptchaHelper::verifyGlobally(new Request(), 'demo_provision'));
    }

    public function test_it_returns_false_when_the_driver_rejects_the_token(): void
    {
        $this->app->instance(CaptchaDriver::class, $this->driver(false));

        $this->assertFalse(CaptchaHelper::verifyGlobally(new Request(), 'demo_provision'));
    }

    public function test_it_fails_closed_when_the_driver_throws(): void
    {
        $this->app->instance(CaptchaDriver::class, new class implements CaptchaDriver
        {
            public function renderScript(): string
            {
                return '';
            }

            public function renderWidget(array $options = []): string
            {
                return '';
            }

            public function verify(Request $request): CaptchaResult
            {
                throw new RuntimeException('boom');
            }

            public function rules(): array
            {
                return [];
            }

            public function isEnabled(): bool
            {
                return true;
            }

            public function cspDirectives(): array
            {
                return [];
            }
        });

        $this->assertFalse(CaptchaHelper::verifyGlobally(new Request(), 'demo_provision'));
    }

    public function test_it_hands_the_action_to_a_driver_that_expects_one(): void
    {
        $seen = new \stdClass();
        $seen->action = null;

        $this->app->instance(CaptchaDriver::class, new class($seen) implements CaptchaDriver, ExpectsAction
        {
            public function __construct(private \stdClass $seen) {}

            public function withExpectedAction(string $action): static
            {
                $this->seen->action = $action;

                return $this;
            }

            public function renderScript(): string
            {
                return '';
            }

            public function renderWidget(array $options = []): string
            {
                return '';
            }

            public function verify(Request $request): CaptchaResult
            {
                return new CaptchaResult(true);
            }

            public function rules(): array
            {
                return [];
            }

            public function isEnabled(): bool
            {
                return true;
            }

            public function cspDirectives(): array
            {
                return [];
            }
        });

        CaptchaHelper::verifyGlobally(new Request(), 'demo_provision');

        $this->assertSame('demo_provision', $seen->action);
    }

    private function driver(bool $success): CaptchaDriver
    {
        return new class($success) implements CaptchaDriver
        {
            public function __construct(private bool $success) {}

            public function renderScript(): string
            {
                return '';
            }

            public function renderWidget(array $options = []): string
            {
                return '';
            }

            public function verify(Request $request): CaptchaResult
            {
                return new CaptchaResult($this->success);
            }

            public function rules(): array
            {
                return [];
            }

            public function isEnabled(): bool
            {
                return true;
            }

            public function cspDirectives(): array
            {
                return [];
            }
        };
    }
}
