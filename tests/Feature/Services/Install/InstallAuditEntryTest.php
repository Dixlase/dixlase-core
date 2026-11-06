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

declare(strict_types=1);

namespace Tests\Feature\Services\Install;

use App\Models\AuditLog;
use App\Models\CoreVersionHistory;
use App\Models\Member;
use App\Services\Core\CoreUpdater;
use App\Services\Install\InstallFinalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The install writes itself into the audit log.
 *
 * Before this, the log's first line on a new site was whatever happened
 * after the install: the event that created the site, and the account
 * that became its first administrator, were the one event the log did not
 * hold. See dixlase-core#917.
 *
 * finalize() is not called here on purpose — it rewrites `.env` — so the
 * step is exercised directly, the way the two methods beside it are.
 *
 * @see \App\Services\Install\InstallFinalizer::recordInstallAudit()
 */
class InstallAuditEntryTest extends TestCase
{
    use RefreshDatabase;

    private function finalizer(): InstallFinalizer
    {
        return app(InstallFinalizer::class);
    }

    /**
     * The row InstallRunner::seedCoreReleaseState() writes.
     */
    private function installLedgerRow(?int $adminId): CoreVersionHistory
    {
        return CoreVersionHistory::create([
            'old_version' => null,
            'new_version' => '0.2.9',
            'installation_method' => CoreVersionHistory::METHOD_INSTALL,
            'applied_by_id' => $adminId,
            'applied_at' => now(),
        ]);
    }

    #[Test]
    public function it_names_the_version_and_the_first_administrator(): void
    {
        $admin = Member::factory()->create();
        $ledger = $this->installLedgerRow($admin->id);

        $row = $this->finalizer()->recordInstallAudit();

        $this->assertNotNull($row);
        $this->assertSame(AuditLog::ACTION_CORE_INSTALLED, $row->action);
        $this->assertSame(AuditLog::CATEGORY_SYSTEM, $row->category);
        $this->assertSame(AuditLog::OUTCOME_SUCCESS, $row->outcome);
        $this->assertSame(AuditLog::SEVERITY_NOTICE, $row->severity);
        $this->assertSame($admin->id, (int) $row->actor_id);
        $this->assertSame('Dixlase core v0.2.9', $row->target_label);
        $this->assertSame('0.2.9', $row->context['version']);
        $this->assertSame($ledger->id, $row->context['history_id']);
    }

    #[Test]
    public function the_entry_is_in_the_hash_chain(): void
    {
        $this->installLedgerRow(null);

        $row = $this->finalizer()->recordInstallAudit();

        // AuditLog::log() chains on write (core #629 / PR #666). The
        // chain build that follows in finalize() is a rescue pass, not
        // the thing that is relied on here.
        $this->assertNotNull($row->fresh()->record_hash, 'The install entry must be hash-chained like any other.');
    }

    #[Test]
    public function a_second_run_does_not_claim_a_second_install(): void
    {
        $this->installLedgerRow(null);

        $this->assertNotNull($this->finalizer()->recordInstallAudit());
        $this->assertNull($this->finalizer()->recordInstallAudit());

        $this->assertSame(1, AuditLog::where('action', AuditLog::ACTION_CORE_INSTALLED)->count());
    }

    #[Test]
    public function it_still_records_the_install_without_a_ledger_row(): void
    {
        // No core_version_history row at all: an install path that never
        // seeded one, or one seeded after this step. The event is still
        // worth recording — with the version from disk and no actor.
        $this->assertSame(0, CoreVersionHistory::count());

        $row = $this->finalizer()->recordInstallAudit();

        $this->assertNotNull($row);
        $this->assertNull($row->actor_id);
        $this->assertSame(CoreUpdater::readVersionFromDisk(), $row->context['version']);
        $this->assertNull($row->context['history_id']);
    }

    #[Test]
    public function it_prefers_the_row_the_finalizer_just_created(): void
    {
        $admin = Member::factory()->create();
        $baseline = $this->installLedgerRow($admin->id);

        $row = $this->finalizer()->recordInstallAudit($baseline);

        $this->assertSame($baseline->id, $row->context['history_id']);
    }

    #[Test]
    public function both_install_paths_reach_it_through_the_finalizer(): void
    {
        $finalizer = (string) file_get_contents(app_path('Services/Install/InstallFinalizer.php'));

        $this->assertMatchesRegularExpression(
            '/public function finalize\(\).*?\$this->recordInstallAudit\(\$baseline\);/s',
            $finalizer,
            'finalize() must record the install; it is the one place both paths pass through.'
        );

        $auditAt = strpos($finalizer, '$this->recordInstallAudit($baseline);');
        $chainAt = strpos($finalizer, '$this->buildAuditLogHashChain();');
        $this->assertNotFalse($auditAt);
        $this->assertNotFalse($chainAt);
        $this->assertLessThan(
            $chainAt,
            $auditAt,
            'The entry has to be written before the chain pass, or it is left behind it.'
        );

        // The wizard and the CLI both go through the finalizer rather
        // than finalizing anything themselves.
        $controller = (string) file_get_contents(app_path('Http/Controllers/Install/InstallCompleteController.php'));
        $command = (string) file_get_contents(app_path('Console/Commands/InstallCommand.php'));
        $this->assertStringContainsString('InstallFinalizer::class)->finalize()', $controller);
        // The command takes the finalizer as an injected argument.
        $this->assertStringContainsString('InstallFinalizer $finalizer', $command);
        $this->assertStringContainsString('$finalizer->finalize();', $command);
    }
}
