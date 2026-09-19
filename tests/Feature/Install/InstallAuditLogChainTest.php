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

namespace Tests\Feature\Install;

use App\Http\Controllers\Install\InstallCompleteController;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * The install wizard chains the audit log once at completion so
 * install-time events are protected before the first hourly
 * `audit:integrity build` run. Exercised through the extracted method
 * for the same reason as InstallBaselineVersionHistoryTest: finalize()
 * rewrites .env.
 */
class InstallAuditLogChainTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_completion_chains_every_pending_entry(): void
    {
        for ($i = 0; $i < 3; $i++) {
            AuditLog::create([
                'occurred_at' => now(),
                'severity' => AuditLog::SEVERITY_INFO,
                'outcome' => AuditLog::OUTCOME_SUCCESS,
                'category' => AuditLog::CATEGORY_SYSTEM,
                'action' => 'install',
                'ip_address' => '127.0.0.1',
                'context' => [],
            ]);
        }
        $this->assertSame(3, AuditLog::withoutHashChain()->count());

        $processed = $this->invokeBuild();

        $this->assertSame(3, $processed);
        $this->assertSame(0, AuditLog::withoutHashChain()->count());
        $this->assertSame(AuditLog::GENESIS_HASH, AuditLog::orderBy('id')->first()->previous_hash);
    }

    public function test_install_completion_is_a_noop_without_entries(): void
    {
        $this->assertSame(0, $this->invokeBuild());
    }

    private function invokeBuild(): ?int
    {
        $controller = new InstallCompleteController();
        $method = new ReflectionMethod($controller, 'buildAuditLogHashChain');
        $method->setAccessible(true);

        return $method->invoke($controller);
    }
}
