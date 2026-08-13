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

namespace Tests\Unit\Services\View;

use App\Services\View\CompiledViewCacheRebuilder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CompiledViewCacheRebuilderTest extends TestCase
{
    public function test_it_runs_clear_then_cache_in_order(): void
    {
        $calls = [];
        Artisan::shouldReceive('call')
            ->twice()
            ->withArgs(function (string $cmd) use (&$calls) {
                $calls[] = $cmd;

                return in_array($cmd, ['view:clear', 'view:cache'], true);
            })
            ->andReturn(0);

        CompiledViewCacheRebuilder::rebuild();

        // Order matters: clear MUST come before cache, otherwise
        // cache would compile against the stale-tree state and be
        // immediately dropped by the clear.
        $this->assertSame(['view:clear', 'view:cache'], $calls);
    }

    public function test_a_view_cache_failure_is_logged_and_swallowed(): void
    {
        // Prove the "belt-and-suspenders" behaviour: if the
        // freshly-swapped extension ships a broken Blade file that
        // makes view:cache throw, the caller (an install / update
        // command whose file + migration halves already committed)
        // must not fail. The failure surfaces via the log; the next
        // inbound request compiles the affected view on demand.
        Artisan::shouldReceive('call')
            ->once()
            ->with('view:clear')
            ->andReturn(0);

        Artisan::shouldReceive('call')
            ->once()
            ->with('view:cache')
            ->andThrow(new \RuntimeException('boom (simulated broken blade)'));

        Log::shouldReceive('warning')
            ->once()
            ->withArgs(function (string $message, array $context) {
                $this->assertStringContainsString('view:cache after view:clear failed', $message);
                $this->assertSame('boom (simulated broken blade)', $context['error']);
                $this->assertArrayHasKey('hint', $context);

                return true;
            });

        // Would throw pre-fix — assertion is that it does not.
        CompiledViewCacheRebuilder::rebuild();

        $this->assertTrue(true);
    }

    public function test_a_view_clear_failure_propagates(): void
    {
        // The clear step is not wrapped: if view:clear itself fails,
        // the stale-compiled-tree symptom this whole helper is meant
        // to avoid is guaranteed to bite anyway. Better to surface
        // the failure loudly than paper over it with a warning log
        // that never gets read.
        Artisan::shouldReceive('call')
            ->once()
            ->with('view:clear')
            ->andThrow(new \RuntimeException('clear failed'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('clear failed');

        CompiledViewCacheRebuilder::rebuild();
    }
}
