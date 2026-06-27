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

namespace Tests\Unit\Services\Captcha;

use App\Captcha\TurnstileCaptchaDriver;
use App\Contracts\Theme\SiteAppearanceProviderInterface;
use App\Models\Member;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Pins TurnstileCaptchaDriver::resolveTheme() four-tier order:
 *
 *   1. Explicit operator override (config / env) wins everywhere.
 *   2. Admin auth pages (admin URL + no member logged in) force
 *      `auto`, matching the pre-login layout's OS-only behaviour.
 *   3. Active theme's SiteAppearanceProviderInterface, if bound.
 *   4. `auto` as the safe fallback.
 *
 * The admin auth carve-out is the regression target: before this
 * shape landed, the widget queried the front-theme provider even on
 * the admin login page, so a force-dark theme would render a dark
 * widget on a light page (or vice-versa) since the auth layout
 * itself only follows `prefers-color-scheme`.
 */
class TurnstileCaptchaDriverResolveThemeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset to a clean state: no operator override, no provider
        // binding. Individual tests opt back in.
        config(['captcha.drivers.turnstile.theme' => null]);
        $this->app->forgetInstance(SiteAppearanceProviderInterface::class);
    }

    public function test_explicit_override_wins_on_admin_auth_pages(): void
    {
        $this->bindProvider('dark');
        $this->setRequest('admin/login');
        $this->logoutMember();

        $this->assertSame(
            'dark',
            $this->callResolveTheme(['theme' => 'dark']),
            'config-level theme override must always win, even on admin auth pages',
        );
    }

    public function test_explicit_override_wins_on_front_pages(): void
    {
        $this->bindProvider('dark');
        $this->setRequest('inquiry');

        $this->assertSame(
            'light',
            $this->callResolveTheme(['theme' => 'light']),
            'config-level theme override must win over the provider on front pages too',
        );
    }

    public function test_admin_auth_path_without_member_returns_auto_even_when_provider_says_dark(): void
    {
        $this->bindProvider('dark');
        $this->setRequest('admin/login');
        $this->logoutMember();

        $this->assertSame(
            'auto',
            $this->callResolveTheme([]),
            'admin auth pages must follow OS preference; provider answers must not leak through',
        );
    }

    public function test_admin_auth_path_with_member_logged_in_uses_provider(): void
    {
        $this->bindProvider('dark');
        $this->setRequest('admin/dashboard');
        $this->loginMember();

        $this->assertSame(
            'dark',
            $this->callResolveTheme([]),
            'authenticated admin requests are no longer the pre-login layout and may use the provider',
        );
    }

    public function test_front_path_uses_provider(): void
    {
        $this->bindProvider('light');
        $this->setRequest('inquiry');

        $this->assertSame(
            'light',
            $this->callResolveTheme([]),
            'front pages must keep tracking the active theme provider',
        );
    }

    public function test_falls_back_to_auto_when_no_provider_bound(): void
    {
        $this->setRequest('inquiry');

        $this->assertSame(
            'auto',
            $this->callResolveTheme([]),
            'no provider + no override + non-admin path must resolve to auto',
        );
    }

    public function test_admin_auth_carve_out_honours_custom_admin_url(): void
    {
        // AdminHelper::getAdminUrl() only consults site_settings when
        // the app reports itself as installed. The test bootstrap does
        // not set the INSTALLED env flag, so prime it here so the
        // custom prefix actually flows through to the carve-out check.
        $previousInstalled = $_SERVER['INSTALLED'] ?? null;
        $_SERVER['INSTALLED'] = 'true';

        try {
            SiteSetting::setValue('admin_url', 'manage');
            $this->bindProvider('dark');
            $this->setRequest('manage/login');
            $this->logoutMember();

            $this->assertSame(
                'auto',
                $this->callResolveTheme([]),
                'admin auth detection must use the resolved admin URL, not literal /admin',
            );
        } finally {
            if ($previousInstalled === null) {
                unset($_SERVER['INSTALLED']);
            } else {
                $_SERVER['INSTALLED'] = $previousInstalled;
            }
        }
    }

    public function test_provider_exception_falls_through_to_auto(): void
    {
        $this->app->bind(SiteAppearanceProviderInterface::class, function () {
            return new class implements SiteAppearanceProviderInterface
            {
                public function getAppearanceMode(): string
                {
                    throw new \RuntimeException('theme provider blew up');
                }
            };
        });
        $this->setRequest('inquiry');

        $this->assertSame(
            'auto',
            $this->callResolveTheme([]),
            'a buggy provider must never propagate a 500 — driver must fall through to auto',
        );
    }

    private function callResolveTheme(array $config): string
    {
        $method = new ReflectionMethod(TurnstileCaptchaDriver::class, 'resolveTheme');
        $method->setAccessible(true);

        return (string) $method->invoke(null, $config);
    }

    private function bindProvider(string $mode): void
    {
        $this->app->bind(SiteAppearanceProviderInterface::class, function () use ($mode) {
            return new class($mode) implements SiteAppearanceProviderInterface
            {
                public function __construct(private string $mode) {}

                public function getAppearanceMode(): string
                {
                    return $this->mode;
                }
            };
        });
    }

    private function setRequest(string $path): void
    {
        $this->app->instance('request', Request::create('/'.ltrim($path, '/'), 'GET'));
    }

    private function logoutMember(): void
    {
        auth('member')->logout();
    }

    private function loginMember(): void
    {
        $member = Member::factory()->create();
        auth('member')->login($member);
    }
}
