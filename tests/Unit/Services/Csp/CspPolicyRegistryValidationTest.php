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
use App\Services\Csp\CspBuilder;
use App\Services\Csp\CspExtensionLoader;
use App\Services\Csp\CspPolicyRegistry;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Policy-weakening CSP values from an extension must not reach the header (#494)
 *
 * Every path an extension has into the policy — addDirective(),
 * addDirectives() (manifest `csp` section, RegistersCspPolicy) and
 * CspPolicyProvider — goes through the registry, which drops what
 * CspSourceValidator rejects and keeps the rest.
 */
class CspPolicyRegistryValidationTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpDir = storage_path('framework/testing/csp-manifest-'.uniqid());
        File::ensureDirectoryExists($this->tmpDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->tmpDir);

        parent::tearDown();
    }

    public function test_add_directive_drops_dangerous_values_and_keeps_valid_ones(): void
    {
        $log = Log::spy();

        $registry = new CspPolicyRegistry();
        $registry->addDirective('script-src', [
            'https://cdn.example.com',
            "'unsafe-inline'",
            "'unsafe-eval'",
            'data:',
            '*',
        ], 'plugin:bad-plugin');

        $this->assertSame(['script-src' => ['https://cdn.example.com']], $registry->collectDirectives());

        $reasons = array_column($registry->getRejected()['plugin:bad-plugin'], 'reason', 'value');
        $this->assertSame([
            "'unsafe-inline'" => 'unsafe_keyword',
            "'unsafe-eval'" => 'unsafe_keyword',
            'data:' => 'scheme_source',
            '*' => 'wildcard_any',
        ], $reasons);

        $log->shouldHaveReceived('warning')
            ->withArgs(fn ($message, $context) => str_contains($message, 'CSP source from an extension was rejected')
                && $context['source'] === 'plugin:bad-plugin'
                && $context['value'] === "'unsafe-inline'")
            ->once();
    }

    public function test_add_directives_rejects_directives_extensions_may_not_widen(): void
    {
        $registry = new CspPolicyRegistry();
        $registry->addDirectives([
            'frame-ancestors' => ['https://evil.example.com'],
            'img-src' => ['https://img.example.com', 'data:'],
        ], 'theme:some-theme');

        $this->assertSame(['img-src' => ['https://img.example.com', 'data:']], $registry->collectDirectives());
    }

    public function test_provider_directives_are_validated(): void
    {
        $registry = new CspPolicyRegistry();
        $registry->registerProvider('bad-provider', new class implements CspPolicyProvider
        {
            public function getCspDirectives(): array
            {
                return [
                    'script-src' => ['https://www.googletagmanager.com', "'unsafe-inline'", 'https://*'],
                    'connect-src' => ['https://*.google-analytics.com'],
                ];
            }
        });

        $this->assertSame([
            'script-src' => ['https://www.googletagmanager.com'],
            'connect-src' => ['https://*.google-analytics.com'],
        ], $registry->collectDirectives());
        $this->assertCount(2, $registry->getRejected()['bad-provider']);
    }

    public function test_dangerous_extension_values_do_not_reach_the_header(): void
    {
        $builder = app(CspBuilder::class);
        $baseline = $this->scriptSrc($builder->build());

        app(CspPolicyRegistry::class)->addDirectives([
            'script-src' => [
                'https://cdn.extension.example',
                "'unsafe-hashes'",
                "'wasm-unsafe-eval'",
                'https://*',
                'blob:',
                'https://a.example.com; script-src *',
            ],
        ], 'plugin:bad-plugin');

        $withExtension = $this->scriptSrc($builder->build());

        $this->assertSame(
            ['https://cdn.extension.example'],
            array_values(array_diff($withExtension, $baseline)),
            'Only the concrete host from the extension may be added to script-src'
        );
    }

    public function test_manifest_values_are_validated_as_written(): void
    {
        // `*` used to be normalised to `https://*`, which is a valid source
        // matching every HTTPS origin; it must now be rejected as written.
        $manifest = $this->tmpDir.'/plugin.json';
        File::put($manifest, json_encode([
            'csp' => [
                'external_domains' => [
                    'scripts' => ['cdn.example.com', '*', "'unsafe-inline'"],
                    'images' => ['data:', 'https://*.example.com'],
                ],
            ],
        ]));

        $loader = new class(app(CspPolicyRegistry::class)) extends CspExtensionLoader
        {
            public function load(string $path): array
            {
                return $this->loadFromJson($path, 'plugin', 'manifest-probe-'.uniqid());
            }
        };

        $loader->load($manifest);

        $this->assertSame([
            'script-src' => ['https://cdn.example.com'],
            'img-src' => ['data:', 'https://*.example.com'],
        ], app(CspPolicyRegistry::class)->collectDirectives());

        $this->assertSame([
            ['directive' => 'script-src', 'value' => '*', 'reason' => 'wildcard_any'],
            ['directive' => 'script-src', 'value' => "'unsafe-inline'", 'reason' => 'unsafe_keyword'],
        ], $loader->getRejectedSourcesFromManifest($manifest));
    }

    /**
     * @return array<int, string>
     */
    private function scriptSrc(string $header): array
    {
        foreach (explode(';', $header) as $directive) {
            $parts = preg_split('/\s+/', trim($directive)) ?: [];
            if (($parts[0] ?? '') === 'script-src') {
                return array_slice($parts, 1);
            }
        }

        $this->fail('script-src missing from the CSP header: '.$header);
    }
}
