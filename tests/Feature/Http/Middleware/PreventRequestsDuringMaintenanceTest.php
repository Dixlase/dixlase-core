<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace Tests\Feature\Http\Middleware;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Pins the Round 5 Finding D root fix: Dixlase's global middleware
 * stack MUST include Laravel's PreventRequestsDuringMaintenance so
 * that `Artisan::call('down')` from CoreUpdater / CoreRollback
 * actually blocks incoming requests during the source-swap window.
 *
 * Before this fix, `bootstrap/app.php`'s `$middleware->use([...])`
 * replaced Laravel's default global stack and dropped this middleware
 * — so `down` wrote the maintenance sentinel but no request-side
 * middleware ever read it, and requests reached a mid-swapped source
 * tree. The sandbox dryrun-10 verification caught the resulting
 * transient 500s (session driver not supported, require(AssetHelper)
 * fatal, half-written composer/installed.php parse error).
 */
class PreventRequestsDuringMaintenanceTest extends TestCase
{
    protected function tearDown(): void
    {
        // Ensure any leftover maintenance state is cleared even if a
        // test failed mid-way — otherwise the whole suite serves 503s
        // and unrelated tests break.
        if ($this->laravel_maintenance_active()) {
            Artisan::call('up');
        }
        parent::tearDown();
    }

    public function test_down_returns_503_for_a_normal_request(): void
    {
        Artisan::call('down', ['--retry' => 60, '--refresh' => 15]);

        try {
            // Any bare path Laravel routes will do — Dixlase's
            // fallback root route serves the front page normally.
            $response = $this->get('/');

            $this->assertSame(
                503,
                $response->getStatusCode(),
                'With PreventRequestsDuringMaintenance in the global '
                .'middleware stack, `Artisan::call(\'down\')` MUST return '
                .'503 for a normal request. If this is 200, Dixlase\'s '
                .'custom `use([...])` in bootstrap/app.php has silently '
                .'dropped the middleware again — Round 5 Finding D root '
                .'cause regressed. See PR-O.'
            );
        } finally {
            Artisan::call('up');
        }
    }

    public function test_fpm_cache_reset_hook_stays_reachable_during_maintenance(): void
    {
        // The fpm-cache-reset internal hook is the ONE endpoint
        // PhpFpmReloader hits from the CLI update process while `down`
        // is still active — before lifting maintenance. If maintenance
        // 503s this route, the reset never happens and the whole
        // Round 5 PR-N mitigation breaks.
        config(['core_update.fpm_reload.http_token' => 'test-token']);
        $this->withoutMiddleware(\App\Http\Middleware\CheckInstallationReady::class);

        Artisan::call('down', ['--retry' => 60, '--refresh' => 15]);

        try {
            $response = $this->postJson('/system/fpm-cache-reset', [], [
                'X-Fpm-Cache-Reset-Token' => 'test-token',
            ]);

            $this->assertSame(
                200,
                $response->getStatusCode(),
                'The fpm-cache-reset route MUST be exempt from '
                .'PreventRequestsDuringMaintenance — it is called by '
                .'PhpFpmReloader from the CLI update process while '
                .'maintenance is still active, and a 503 here breaks '
                .'the FPM opcache/realpath refresh path (PR-N).'
            );
        } finally {
            Artisan::call('up');
        }
    }

    public function test_up_lifts_maintenance_and_restores_normal_traffic(): void
    {
        Artisan::call('down', ['--retry' => 60, '--refresh' => 15]);
        $this->assertSame(503, $this->get('/')->getStatusCode());

        Artisan::call('up');

        $response = $this->get('/');
        $this->assertNotSame(
            503,
            $response->getStatusCode(),
            'After `Artisan::call(\'up\')` requests must resume normally — '
            .'a persistent 503 here means the maintenance sentinel '
            .'was not cleared, and every future update would leave the '
            .'site permanently unreachable.'
        );
    }

    private function laravel_maintenance_active(): bool
    {
        return is_file(storage_path('framework/maintenance.php'));
    }
}
