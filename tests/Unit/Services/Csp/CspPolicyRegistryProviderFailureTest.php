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

namespace Tests\Unit\Services\Csp;

use App\Contracts\CspPolicyProvider;
use App\Services\Csp\CspPolicyRegistry;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * One failing CSP provider must not take the request down.
 *
 * Providers are third-party code (plugins, themes) reached from the
 * ContentSecurityPolicy middleware, which runs on EVERY request. An exception
 * escaping collectDirectives() is therefore not a CSP problem — it is a site
 * outage.
 *
 * That happened: DixlaseSEO read its own settings table inside
 * getCspDirectives(), and while the plugin was enabled without its schema the
 * PDOException returned 500 for every page of the site.
 */
class CspPolicyRegistryProviderFailureTest extends TestCase
{
    private function provider(callable $directives): CspPolicyProvider
    {
        return new class($directives) implements CspPolicyProvider
        {
            /** @var callable */
            private $directives;

            public function __construct(callable $directives)
            {
                $this->directives = $directives;
            }

            public function getCspDirectives(): array
            {
                return ($this->directives)();
            }
        };
    }

    public function test_a_throwing_provider_does_not_propagate(): void
    {
        Log::spy();

        $registry = new CspPolicyRegistry();
        $registry->registerProvider('exploding', $this->provider(
            fn () => throw new \RuntimeException('no such table: dls_plg_example_settings')
        ));

        $this->assertSame([], $registry->collectDirectives());
    }

    public function test_an_error_is_caught_too_not_just_an_exception(): void
    {
        // A missing class or a driver fault arrives as \Error, which does not
        // extend \Exception — catching only \Exception would let it through.
        Log::spy();

        $registry = new CspPolicyRegistry();
        $registry->registerProvider('missing-class', $this->provider(
            fn () => throw new \Error('Class "App\Models\Nope" not found')
        ));

        $this->assertSame([], $registry->collectDirectives());
    }

    public function test_healthy_providers_still_contribute_when_another_fails(): void
    {
        // The point of skipping rather than aborting: the rest of the policy
        // must survive one bad neighbour.
        Log::spy();

        $registry = new CspPolicyRegistry();
        $registry->registerProvider('broken', $this->provider(
            fn () => throw new \RuntimeException('boom')
        ));
        $registry->registerProvider('healthy', $this->provider(
            fn () => ['script-src' => ['https://example.test']]
        ));

        $this->assertSame(
            ['script-src' => ['https://example.test']],
            $registry->collectDirectives()
        );
    }

    public function test_the_failure_is_logged_rather_than_swallowed(): void
    {
        // A silent skip would hide exactly the class of defect this catch
        // exists to survive.
        Log::spy();

        $registry = new CspPolicyRegistry();
        $registry->registerProvider('exploding', $this->provider(
            fn () => throw new \RuntimeException('no such table: dls_plg_example_settings')
        ));

        $registry->collectDirectives();

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return str_contains($message, 'CSP provider failed')
                    && $context['provider'] === 'exploding'
                    && str_contains($context['error'], 'no such table');
            });
    }

    public function test_directives_registered_directly_are_unaffected(): void
    {
        Log::spy();

        $registry = new CspPolicyRegistry();
        $registry->addDirective('img-src', ['https://cdn.example.test']);
        $registry->registerProvider('broken', $this->provider(
            fn () => throw new \RuntimeException('boom')
        ));

        $this->assertSame(
            ['img-src' => ['https://cdn.example.test']],
            $registry->collectDirectives()
        );
    }
}
