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

namespace Tests\Unit\Services\Front;

use App\Contracts\Front\FrontPageBodyProviderInterface;
use App\DTO\Front\FrontPageBodyContext;
use App\DTO\Front\FrontPageBodyDTO;
use App\DTO\Front\FrontPageBodyResult;
use App\Services\Front\FrontPageBodyResolver;
use App\Services\Plugin\PluginPermissionService;
use App\Services\Plugin\PluginServiceResolver;
use Mockery;
use Tests\TestCase;

/**
 * FrontPageBodyResolver aggregates the extensions that can fill a theme's front
 * page body, and renders the one an operator picked. Every way a stored choice can
 * fail has to come back as null, because that is the single case a theme handles by
 * rendering core's front page content instead.
 */
class FrontPageBodyResolverTest extends TestCase
{
    protected PluginServiceResolver $resolver;

    protected FrontPageBodyResolver $bodies;

    protected function setUp(): void
    {
        parent::setUp();

        $permissionService = Mockery::mock(PluginPermissionService::class);
        $permissionService->shouldReceive('check')->andReturn(true)->byDefault();

        $this->resolver = new PluginServiceResolver($permissionService);
        $this->app->instance(PluginServiceResolver::class, $this->resolver);

        $this->bodies = new FrontPageBodyResolver($this->resolver);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @param  array<string, string>  $choices  body id => label
     */
    private function provider(
        string $slug,
        string $label,
        array $choices,
        bool $available = true,
    ): FrontPageBodyProviderInterface {
        return new class($slug, $label, $choices, $available) implements FrontPageBodyProviderInterface
        {
            /** @param array<string, string> $choices */
            public function __construct(
                private string $slug,
                private string $label,
                private array $choices,
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

            public function getFrontPageBodyLabel(): string
            {
                return $this->label;
            }

            public function availableFrontPageBodies(int $limit = 100): array
            {
                $bodies = [];

                foreach ($this->choices as $id => $label) {
                    $bodies[] = new FrontPageBodyDTO(id: (string) $id, label: $label);
                }

                return array_slice($bodies, 0, $limit);
            }

            public function renderFrontPageBody(string $bodyId, FrontPageBodyContext $context): ?FrontPageBodyResult
            {
                if (! isset($this->choices[$bodyId])) {
                    return null;
                }

                return new FrontPageBodyResult(content: '<p>'.$this->slug.'/'.$bodyId.'</p>');
            }
        };
    }

    private function register(FrontPageBodyProviderInterface $provider): void
    {
        $this->resolver->register(FrontPageBodyProviderInterface::class, $provider);
    }

    public function test_choice_key_round_trips(): void
    {
        $key = FrontPageBodyResolver::choiceKey('dixlase-blog', 'top');

        $this->assertSame('dixlase-blog:top', $key);
        $this->assertSame(
            ['provider_key' => 'dixlase-blog', 'body_id' => 'top'],
            FrontPageBodyResolver::splitChoiceKey($key),
        );
    }

    public function test_split_choice_key_keeps_a_separator_inside_the_body_id(): void
    {
        $this->assertSame(
            ['provider_key' => 'dixlase-pages', 'body_id' => 'a:b'],
            FrontPageBodyResolver::splitChoiceKey('dixlase-pages:a:b'),
        );
    }

    public function test_split_choice_key_rejects_values_that_are_not_choices(): void
    {
        $this->assertNull(FrontPageBodyResolver::splitChoiceKey(FrontPageBodyResolver::BODY_CORE));
        $this->assertNull(FrontPageBodyResolver::splitChoiceKey(''));
        $this->assertNull(FrontPageBodyResolver::splitChoiceKey(':top'));
        $this->assertNull(FrontPageBodyResolver::splitChoiceKey('dixlase-blog:'));
    }

    public function test_with_no_provider_nothing_is_offered(): void
    {
        $this->assertSame([], $this->bodies->groupedBodies());
        $this->assertSame([], $this->bodies->availableBodies());
    }

    public function test_grouped_bodies_groups_choices_under_their_provider(): void
    {
        $this->register($this->provider('plugin-blog', 'Blog', ['top' => 'Blog top']));
        $this->register($this->provider('plugin-pages', 'Pages', ['12' => 'About', '13' => 'Contact']));

        $groups = $this->bodies->groupedBodies();

        $this->assertSame(['plugin-blog', 'plugin-pages'], array_keys($groups));
        $this->assertSame('Blog', $groups['plugin-blog']['label']);
        $this->assertSame(['plugin-blog:top' => 'Blog top'], $groups['plugin-blog']['choices']);
        $this->assertSame([
            'plugin-pages:12' => 'About',
            'plugin-pages:13' => 'Contact',
        ], $groups['plugin-pages']['choices']);
    }

    public function test_available_bodies_flattens_every_choice(): void
    {
        $this->register($this->provider('plugin-blog', 'Blog', ['top' => 'Blog top']));
        $this->register($this->provider('plugin-pages', 'Pages', ['12' => 'About']));

        $this->assertSame([
            'plugin-blog:top' => 'Blog top',
            'plugin-pages:12' => 'About',
        ], $this->bodies->availableBodies());
    }

    /**
     * A provider with nothing to show right now is left out rather than offered as
     * an empty group an operator could pick from.
     */
    public function test_a_provider_with_no_choices_is_not_offered(): void
    {
        $this->register($this->provider('plugin-pages', 'Pages', []));

        $this->assertSame([], $this->bodies->groupedBodies());
    }

    public function test_an_unavailable_provider_is_not_offered(): void
    {
        $this->register($this->provider('plugin-blog', 'Blog', ['top' => 'Blog top'], available: false));

        $this->assertSame([], $this->bodies->availableBodies());
        $this->assertFalse($this->bodies->has('plugin-blog:top'));
    }

    public function test_the_limit_is_applied_per_provider(): void
    {
        $this->register($this->provider('plugin-pages', 'Pages', [
            '1' => 'One', '2' => 'Two', '3' => 'Three',
        ]));

        $this->assertCount(2, $this->bodies->availableBodies(2));
    }

    public function test_render_returns_the_body_of_the_stored_choice(): void
    {
        $this->register($this->provider('plugin-blog', 'Blog', ['top' => 'Blog top']));
        $this->register($this->provider('plugin-pages', 'Pages', ['12' => 'About']));

        $result = $this->bodies->render('plugin-pages:12', new FrontPageBodyContext());

        $this->assertInstanceOf(FrontPageBodyResult::class, $result);
        $this->assertSame('<p>plugin-pages/12</p>', $result->html());
    }

    public function test_render_returns_null_for_the_core_sentinel(): void
    {
        $this->register($this->provider('plugin-blog', 'Blog', ['top' => 'Blog top']));

        $this->assertNull(
            $this->bodies->render(FrontPageBodyResolver::BODY_CORE, new FrontPageBodyContext())
        );
    }

    public function test_render_returns_null_for_an_unregistered_provider(): void
    {
        $this->register($this->provider('plugin-blog', 'Blog', ['top' => 'Blog top']));

        $this->assertNull($this->bodies->render('plugin-ghost:top', new FrontPageBodyContext()));
    }

    public function test_render_returns_null_when_the_provider_declines_the_id(): void
    {
        $this->register($this->provider('plugin-pages', 'Pages', ['12' => 'About']));

        $this->assertNull($this->bodies->render('plugin-pages:999', new FrontPageBodyContext()));
    }

    public function test_render_returns_null_for_a_malformed_choice(): void
    {
        $this->register($this->provider('plugin-blog', 'Blog', ['top' => 'Blog top']));

        $this->assertNull($this->bodies->render('', new FrontPageBodyContext()));
        $this->assertNull($this->bodies->render('plugin-blog', new FrontPageBodyContext()));
    }

    public function test_has_reports_whether_the_provider_is_present(): void
    {
        $this->register($this->provider('plugin-blog', 'Blog', ['top' => 'Blog top']));

        $this->assertTrue($this->bodies->has('plugin-blog:top'));
        // has() does not ask whether the id still resolves — only render() can.
        $this->assertTrue($this->bodies->has('plugin-blog:gone'));
        $this->assertFalse($this->bodies->has('plugin-ghost:top'));
        $this->assertFalse($this->bodies->has(FrontPageBodyResolver::BODY_CORE));
    }

    /**
     * The stored form is composed from the provider, so a provider cannot hand back a
     * choice that resolves to another plugin.
     */
    public function test_a_choice_is_keyed_by_the_offering_provider(): void
    {
        $this->register($this->provider('plugin-pages', 'Pages', ['12' => 'About']));

        $this->assertSame(['plugin-pages:12' => 'About'], $this->bodies->availableBodies());
    }

    public function test_the_context_reaches_the_provider(): void
    {
        $provider = new class implements FrontPageBodyProviderInterface
        {
            public ?FrontPageBodyContext $seen = null;

            public function getPluginSlug(): string
            {
                return 'plugin-blog';
            }

            public function isCapabilityAvailable(): bool
            {
                return true;
            }

            public function getFrontPageBodyLabel(): string
            {
                return 'Blog';
            }

            public function availableFrontPageBodies(int $limit = 100): array
            {
                return [new FrontPageBodyDTO(id: 'top', label: 'Blog top')];
            }

            public function renderFrontPageBody(string $bodyId, FrontPageBodyContext $context): ?FrontPageBodyResult
            {
                $this->seen = $context;

                return new FrontPageBodyResult(content: 'ok');
            }
        };

        $this->resolver->register(FrontPageBodyProviderInterface::class, $provider);

        $context = new FrontPageBodyContext(
            surface: FrontPageBodyProviderInterface::SURFACE_PREVIEW,
            locale: 'ja',
            page: 2,
            showHeading: false,
        );

        $this->bodies->render('plugin-blog:top', $context);

        $this->assertSame($context, $provider->seen);
        $this->assertTrue($provider->seen->isPreview());
        $this->assertFalse($provider->seen->showHeading);
    }
}
