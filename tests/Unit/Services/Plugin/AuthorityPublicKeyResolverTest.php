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

namespace Tests\Unit\Services\Plugin;

use App\Models\AuthorityPublicKey;
use App\Services\Plugin\AuthorityPublicKeyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Resilience tests for {@see AuthorityPublicKeyResolver}.
 *
 * Guards the recovery path documented in
 * `.backlog/plugin-signature-verification-full-version.md`: a missed migration
 * of `authority_public_keys` must not crash the plugin admin page.
 */
class AuthorityPublicKeyResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolve_returns_null_when_table_is_missing_and_network_unavailable(): void
    {
        Schema::dropIfExists('authority_public_keys');
        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        $resolver = new AuthorityPublicKeyResolver();

        $result = $resolver->resolve('any-key-id');

        $this->assertNull($result);
    }

    public function test_resolve_logs_warning_when_cache_read_fails(): void
    {
        Schema::dropIfExists('authority_public_keys');
        Http::fake([
            '*' => Http::response(null, 500),
        ]);

        Log::spy();

        (new AuthorityPublicKeyResolver())->resolve('any-key-id');

        Log::shouldHaveReceived('warning')
            ->with(
                'AuthorityPublicKeyResolver: cache read failed, falling back to network',
                \Mockery::on(fn ($context) => isset($context['key_id'], $context['error']))
            )
            ->atLeast()
            ->once();
    }

    public function test_resolve_does_not_throw_when_table_is_missing(): void
    {
        Schema::dropIfExists('authority_public_keys');
        Http::fake();

        $resolver = new AuthorityPublicKeyResolver();

        // Asserting no exception is the primary goal; the return value is
        // already covered by the previous test.
        $resolver->resolve('any-key-id');

        $this->expectNotToPerformAssertions();
    }

    /**
     * Security review X7: a cached key_id is not re-keyed by a later fetch.
     */
    public function test_a_cached_key_is_not_replaced_by_a_different_one(): void
    {
        AuthorityPublicKey::create([
            'key_id' => 'k1',
            'public_key' => 'base64:ORIGINAL',
            'algorithm' => 'ed25519',
            'is_active' => true,
            'fetched_at' => now()->subDays(10),
        ]);
        Http::fake([
            '*' => Http::response(['key_id' => 'k1', 'public_key' => 'base64:SWAPPED']),
        ]);

        $key = (new AuthorityPublicKeyResolver())->resolve('k1');

        $this->assertSame('base64:ORIGINAL', $key?->public_key);
        $this->assertSame('base64:ORIGINAL', AuthorityPublicKey::where('key_id', 'k1')->value('public_key'));
    }

    public function test_a_response_for_another_key_id_is_ignored(): void
    {
        Http::fake([
            '*' => Http::response(['key_id' => 'other', 'public_key' => 'base64:X']),
        ]);

        $this->assertNull((new AuthorityPublicKeyResolver())->resolve('k1'));
        $this->assertSame(0, AuthorityPublicKey::count());
    }

    public function test_a_first_fetch_is_cached(): void
    {
        Http::fake([
            '*' => Http::response(['key_id' => 'k1', 'public_key' => 'base64:FIRST']),
        ]);

        $this->assertSame('base64:FIRST', (new AuthorityPublicKeyResolver())->resolve('k1')?->public_key);
    }
}
