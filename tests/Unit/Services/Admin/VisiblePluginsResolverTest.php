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

namespace Tests\Unit\Services\Admin;

use App\Contracts\PluginIntegration\VisiblePluginsProviderInterface;
use App\Services\Admin\VisiblePluginsResolver;
use App\Services\Plugin\PluginPermissionService;
use App\Services\Plugin\PluginServiceResolver;
use Mockery;
use Tests\TestCase;

/**
 * VisiblePluginsResolver merges the plugin-slug allowlist from every registered
 * VisiblePluginsProviderInterface, falls back to the legacy
 * `demo.visible_plugin_slugs` config while none is registered, and answers null
 * (no restriction) when neither a provider nor the config imposes one.
 */
class VisiblePluginsResolverTest extends TestCase
{
    protected PluginPermissionService|Mockery\MockInterface $permissionService;

    protected PluginServiceResolver $resolver;

    protected VisiblePluginsResolver $visible;

    protected function setUp(): void
    {
        parent::setUp();

        $this->permissionService = Mockery::mock(PluginPermissionService::class);
        $this->permissionService->shouldReceive('check')->andReturn(true)->byDefault();

        $this->resolver = new PluginServiceResolver($this->permissionService);
        $this->app->instance(PluginServiceResolver::class, $this->resolver);

        config(['demo.visible_plugin_slugs' => null]);

        $this->visible = new VisiblePluginsResolver($this->resolver);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_no_restriction_when_no_provider_and_no_config(): void
    {
        $this->assertNull($this->visible->allowedSlugs());
    }

    public function test_falls_back_to_the_legacy_config_when_no_provider_is_registered(): void
    {
        config(['demo.visible_plugin_slugs' => ['dixlase-blog', 'dixlase-pages']]);

        $this->assertSame(['dixlase-blog', 'dixlase-pages'], $this->visible->allowedSlugs());
    }

    public function test_a_registered_provider_replaces_the_legacy_config(): void
    {
        config(['demo.visible_plugin_slugs' => ['dixlase-legacy']]);
        $this->resolver->register(
            VisiblePluginsProviderInterface::class,
            $this->makeProvider('demo', ['dixlase-blog', 'dixlase-seo'])
        );

        $this->assertSame(['dixlase-blog', 'dixlase-seo'], $this->visible->allowedSlugs());
    }

    public function test_a_provider_that_imposes_no_restriction_defers_to_the_config(): void
    {
        config(['demo.visible_plugin_slugs' => ['dixlase-blog']]);
        $this->resolver->register(
            VisiblePluginsProviderInterface::class,
            $this->makeProvider('passive', null)
        );

        $this->assertSame(['dixlase-blog'], $this->visible->allowedSlugs());
    }

    public function test_multiple_providers_are_merged_and_deduped(): void
    {
        $this->resolver->register(
            VisiblePluginsProviderInterface::class,
            $this->makeProvider('a', ['dixlase-blog', 'dixlase-shared'])
        );
        $this->resolver->register(
            VisiblePluginsProviderInterface::class,
            $this->makeProvider('b', ['dixlase-seo', 'dixlase-shared'])
        );

        $slugs = $this->visible->allowedSlugs();

        $this->assertEqualsCanonicalizing(['dixlase-blog', 'dixlase-shared', 'dixlase-seo'], $slugs);
        $this->assertCount(3, $slugs, 'duplicate slugs must be deduped');
    }

    public function test_an_unavailable_provider_is_ignored(): void
    {
        config(['demo.visible_plugin_slugs' => ['dixlase-blog']]);
        $this->resolver->register(
            VisiblePluginsProviderInterface::class,
            $this->makeProvider('disabled', ['dixlase-seo'], available: false)
        );

        // The provider is skipped, so the legacy config still applies.
        $this->assertSame(['dixlase-blog'], $this->visible->allowedSlugs());
    }

    public function test_an_empty_provider_list_hides_every_plugin(): void
    {
        config(['demo.visible_plugin_slugs' => ['dixlase-blog']]);
        $this->resolver->register(
            VisiblePluginsProviderInterface::class,
            $this->makeProvider('strict', [])
        );

        $this->assertSame([], $this->visible->allowedSlugs());
    }

    public function test_the_answer_is_memoised_until_forget(): void
    {
        $this->assertNull($this->visible->allowedSlugs());

        config(['demo.visible_plugin_slugs' => ['dixlase-blog']]);
        $this->assertNull($this->visible->allowedSlugs(), 'the memoised answer is reused');

        $this->visible->forget();
        $this->assertSame(['dixlase-blog'], $this->visible->allowedSlugs());
    }

    /**
     * @param  list<string>|null  $slugs
     */
    protected function makeProvider(string $slug, ?array $slugs, bool $available = true): VisiblePluginsProviderInterface
    {
        return new class($slug, $slugs, $available) implements VisiblePluginsProviderInterface
        {
            /**
             * @param  list<string>|null  $slugs
             */
            public function __construct(
                private string $slug,
                private ?array $slugs,
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

            public function getVisiblePluginSlugs(): ?array
            {
                return $this->slugs;
            }
        };
    }
}
