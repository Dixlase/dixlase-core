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

namespace Tests\Unit\Services\Front;

use App\Contracts\Front\FrontPageProviderInterface;
use App\Contracts\Repositories\SiteSettingRepositoryInterface;
use App\Services\Front\FrontPageDisplayResolver;
use App\Services\Plugin\PluginPermissionService;
use App\Services\Plugin\PluginServiceResolver;
use Illuminate\Http\Request;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * FrontPageDisplayResolver turns the per-site front_page_display setting into a
 * response override: null for the theme front page, a 403 for `forbidden`, and
 * a registered provider's response for that provider's slug.
 */
class FrontPageDisplayResolverTest extends TestCase
{
    protected PluginServiceResolver $resolver;

    protected FrontPageDisplayResolver $display;

    protected function setUp(): void
    {
        parent::setUp();

        $permissionService = Mockery::mock(PluginPermissionService::class);
        $permissionService->shouldReceive('check')->andReturn(true)->byDefault();

        $this->resolver = new PluginServiceResolver($permissionService);
        $this->app->instance(PluginServiceResolver::class, $this->resolver);

        $this->display = new FrontPageDisplayResolver($this->resolver);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mode(string $value): void
    {
        $repo = Mockery::mock(SiteSettingRepositoryInterface::class);
        $repo->shouldReceive('get')
            ->with(FrontPageDisplayResolver::SETTING_KEY, FrontPageDisplayResolver::MODE_THEME)
            ->andReturn($value);
        $this->app->instance(SiteSettingRepositoryInterface::class, $repo);
    }

    public function test_theme_mode_renders_the_theme_front_page(): void
    {
        $this->mode('theme');

        $this->assertNull($this->display->resolveResponse(new Request()));
    }

    public function test_an_empty_setting_renders_the_theme_front_page(): void
    {
        $this->mode('');

        $this->assertNull($this->display->resolveResponse(new Request()));
    }

    public function test_forbidden_mode_aborts_with_403(): void
    {
        $this->mode('forbidden');

        try {
            $this->display->resolveResponse(new Request());
            $this->fail('Expected a 403.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
    }

    public function test_a_provider_slug_serves_that_providers_response(): void
    {
        $this->mode('docs');
        $this->resolver->register(
            FrontPageProviderInterface::class,
            $this->provider('docs', 'Documentation home', 'DOCS-FRONT')
        );

        $response = $this->display->resolveResponse(new Request());

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('DOCS-FRONT', $response->getContent());
    }

    public function test_an_unregistered_slug_falls_back_to_the_theme_front_page(): void
    {
        $this->mode('ghost');
        $this->resolver->register(
            FrontPageProviderInterface::class,
            $this->provider('docs', 'Documentation home', 'DOCS-FRONT')
        );

        $this->assertNull($this->display->resolveResponse(new Request()));
    }

    public function test_an_unavailable_provider_is_ignored(): void
    {
        $this->mode('docs');
        $this->resolver->register(
            FrontPageProviderInterface::class,
            $this->provider('docs', 'Documentation home', 'DOCS-FRONT', available: false)
        );

        // The provider is skipped, so the theme front page is served.
        $this->assertNull($this->display->resolveResponse(new Request()));
    }

    public function test_available_providers_lists_registered_providers(): void
    {
        $this->resolver->register(
            FrontPageProviderInterface::class,
            $this->provider('docs', 'Documentation home', 'X')
        );
        $this->resolver->register(
            FrontPageProviderInterface::class,
            $this->provider('launcher', 'Demo launcher', 'Y')
        );

        $this->assertSame(
            ['docs' => 'Documentation home', 'launcher' => 'Demo launcher'],
            $this->display->availableProviders()
        );
    }

    private function provider(
        string $slug,
        string $label,
        string $body,
        bool $available = true,
    ): FrontPageProviderInterface {
        return new class($slug, $label, $body, $available) implements FrontPageProviderInterface
        {
            public function __construct(
                private string $slug,
                private string $label,
                private string $body,
                private bool $available,
            ) {}

            public function getPluginSlug(): string
            {
                return $this->slug;
            }

            public function isCapabilityAvailable(): bool
            {
                return $this->available;
            }

            public function getFrontPageLabel(): string
            {
                return $this->label;
            }

            public function renderFrontPage(Request $request): Response
            {
                return new Response($this->body);
            }
        };
    }
}
