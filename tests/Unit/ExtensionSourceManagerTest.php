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

namespace Tests\Unit;

use App\Models\ExtensionSource;
use App\Services\Extension\ExtensionSourceManager;
use App\Services\Extension\GitHubSourceProvider;
use App\Services\Extension\SourceVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExtensionSourceManagerTest extends TestCase
{
    use RefreshDatabase;

    protected ExtensionSourceManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new ExtensionSourceManager(new SourceVerifier());
    }

    public function test_get_available_types_includes_github(): void
    {
        $types = $this->manager->getAvailableTypes();

        $this->assertArrayHasKey('github', $types);
        $this->assertEquals(GitHubSourceProvider::class, $types['github']['class']);
    }

    public function test_register_custom_provider(): void
    {
        $this->manager->registerProvider('custom', GitHubSourceProvider::class);

        $types = $this->manager->getAvailableTypes();
        $this->assertArrayHasKey('custom', $types);
    }

    public function test_make_provider_returns_github_provider(): void
    {
        $source = ExtensionSource::query()->create([
            'name' => 'Test',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'owner' => 'TestOrg',
            'priority' => 0,
        ]);

        $provider = $this->manager->makeProvider($source);

        $this->assertInstanceOf(GitHubSourceProvider::class, $provider);
    }

    public function test_make_provider_throws_for_unknown_type(): void
    {
        $source = ExtensionSource::query()->create([
            'name' => 'Test',
            'type' => 'unknown',
            'base_url' => 'https://example.com',
            'priority' => 0,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No provider registered for source type: unknown');

        $this->manager->makeProvider($source);
    }

    public function test_get_enabled_sources_ordered_by_priority(): void
    {
        ExtensionSource::query()->create([
            'name' => 'Second',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'is_enabled' => true,
            'priority' => 5,
        ]);
        ExtensionSource::query()->create([
            'name' => 'First',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'is_enabled' => true,
            'priority' => 0,
        ]);
        ExtensionSource::query()->create([
            'name' => 'Disabled',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'is_enabled' => false,
            'priority' => 1,
        ]);

        $sources = $this->manager->getEnabledSources();

        $this->assertCount(2, $sources);
        $this->assertEquals('First', $sources->first()->name);
        $this->assertEquals('Second', $sources->last()->name);
    }

    public function test_verify_source_updates_official_status(): void
    {
        if (! extension_loaded('sodium')) {
            $this->markTestSkipped('sodium extension required');
        }

        $keyPair = sodium_crypto_sign_keypair();
        $privateKey = sodium_crypto_sign_secretkey($keyPair);
        $publicKey = sodium_crypto_sign_publickey($keyPair);
        $privateKeyBase64 = sodium_bin2base64($privateKey, SODIUM_BASE64_VARIANT_ORIGINAL);
        $publicKeyBase64 = sodium_bin2base64($publicKey, SODIUM_BASE64_VARIANT_ORIGINAL);

        config(['extension-sources.public_key' => $publicKeyBase64]);

        $source = ExtensionSource::query()->create([
            'name' => 'Verify Test',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'owner' => 'Dixlase',
            'priority' => 0,
        ]);

        $verifier = new SourceVerifier();
        $signature = $verifier->sign($source, $privateKeyBase64);
        $source->update(['official_signature' => $signature]);
        $source->refresh();

        $result = $this->manager->verifySource($source);
        $source->refresh();

        $this->assertTrue($result['verified']);
        $this->assertTrue($source->is_official);

        config(['extension-sources.public_key' => null]);
    }

    public function test_list_available_plugins_aggregates_across_sources(): void
    {
        ExtensionSource::query()->create([
            'name' => 'Source A',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'owner' => 'OrgA',
            'auth_token' => 'token_a',
            'is_enabled' => true,
            'priority' => 0,
        ]);

        Http::fake([
            'api.github.com/orgs/OrgA/repos*' => Http::sequence()
                ->push([
                    ['name' => 'plugin-dixlase-pages', 'description' => 'Pages'],
                ])
                ->push([]),
        ]);

        $plugins = $this->manager->listAvailablePlugins();

        $this->assertNotEmpty($plugins);
        $this->assertEquals('dixlase-pages', $plugins[0]['slug']);
        $this->assertArrayHasKey('source_id', $plugins[0]);
        $this->assertArrayHasKey('source_name', $plugins[0]);
    }

    public function test_download_throws_when_all_sources_fail(): void
    {
        Http::fake([
            '*' => Http::response([], 404),
        ]);

        ExtensionSource::query()->create([
            'name' => 'Failing Source',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'owner' => 'TestOrg',
            'is_enabled' => true,
            'priority' => 0,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to download');

        $this->manager->download('nonexistent');
    }

    /**
     * Security review X1: an update names the extension's linked source,
     * and a failure there must not fall through to another source that
     * happens to publish a repository with the same name.
     */
    public function test_download_with_a_source_never_falls_back_to_other_sources(): void
    {
        Http::fake([
            '*' => Http::response([], 404),
        ]);

        $linked = ExtensionSource::query()->create([
            'name' => 'Linked Source',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'owner' => 'LinkedOrg',
            'is_enabled' => true,
            'priority' => 1,
        ]);
        ExtensionSource::query()->create([
            'name' => 'Other Source',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'owner' => 'OtherOrg',
            'is_enabled' => true,
            'priority' => 0,
        ]);

        try {
            $this->manager->download('dixlase-pages', 'plugin', '0.1.1', $linked->id);
            $this->fail('A failed linked source must not be replaced by another source.');
        } catch (\RuntimeException) {
            // expected
        }

        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'OtherOrg'));
    }

    public function test_download_with_a_disabled_source_is_refused(): void
    {
        Http::fake();

        $linked = ExtensionSource::query()->create([
            'name' => 'Disabled Source',
            'type' => 'github',
            'base_url' => 'https://api.github.com',
            'owner' => 'LinkedOrg',
            'is_enabled' => false,
            'priority' => 0,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('missing or disabled');

        $this->manager->download('dixlase-pages', 'plugin', '0.1.1', $linked->id);
    }
}
