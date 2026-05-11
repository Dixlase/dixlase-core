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
}
