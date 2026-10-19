<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Services\AuditLogIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Core #629: an audit row used to stay outside the hash chain until the
 * hourly `audit:integrity build` linked it. It is now linked in the same
 * transaction that writes it, under a lock that keeps concurrent writers
 * in id order.
 */
class HashChainOnWriteTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogIntegrityService $integrity;

    protected function setUp(): void
    {
        parent::setUp();

        AuditLog::forgetChainLockAvailability();
        $this->integrity = app(AuditLogIntegrityService::class);
    }

    protected function tearDown(): void
    {
        AuditLog::forgetChainLockAvailability();

        parent::tearDown();
    }

    public function test_a_row_is_chained_when_it_is_written(): void
    {
        $first = $this->write('first');
        $second = $this->write('second');

        $this->assertSame(AuditLog::GENESIS_HASH, $first->previous_hash);
        $this->assertSame(1, $first->chain_sequence);
        $this->assertSame($first->record_hash, $second->previous_hash);
        $this->assertSame(2, $second->chain_sequence);
        $this->assertTrue($this->integrity->verifyChain()['is_valid']);
    }

    public function test_waiting_rows_are_linked_before_the_new_one(): void
    {
        $this->write('chained');
        $waiting = [$this->unchained('waiting-1'), $this->unchained('waiting-2')];

        $new = $this->write('new');

        foreach ($waiting as $row) {
            $this->assertNotNull($row->fresh()->record_hash);
        }
        $this->assertSame(4, $new->chain_sequence);
        $this->assertSame($waiting[1]->fresh()->record_hash, $new->previous_hash);
        $this->assertTrue($this->integrity->verifyChain()['is_valid']);
    }

    public function test_a_long_backlog_is_left_to_build_without_breaking_the_order(): void
    {
        for ($i = 0; $i <= AuditLog::CHAIN_CATCH_UP_LIMIT; $i++) {
            $this->unchained("backlog-{$i}");
        }

        $new = $this->write('new');

        $this->assertNull($new->record_hash);
        $this->assertSame(1, AuditLog::whereNull('record_hash')->count() - 1);

        $result = $this->integrity->buildPendingChains();

        $this->assertSame(2, $result['processed']);
        $this->assertSame(0, $result['remaining']);
        $this->assertTrue($this->integrity->verifyChain()['is_valid']);
    }

    public function test_a_row_that_cannot_be_linked_is_still_written(): void
    {
        // Taking the lock table away is DDL, and MySQL commits the open
        // transaction when DDL runs. That ends the transaction
        // RefreshDatabase wraps the test in, so the savepoint it rolls back
        // to in tearDown is no longer there and the rollback itself fails
        // (SQLite allows the same DDL inside a transaction, which is why
        // this only showed up in CI). The case therefore runs on a
        // throwaway database of its own, outside that transaction.
        $path = storage_path('framework/testing/audit-chain-'.uniqid().'.sqlite');
        @mkdir(dirname($path), 0777, true);
        touch($path);

        $default = config('database.default');
        config(['database.connections.audit_chain' => array_merge(config('database.connections.sqlite'), [
            'database' => $path,
        ])]);

        try {
            Artisan::call('migrate', ['--database' => 'audit_chain', '--path' => 'database/migrations', '--force' => true]);
            DB::purge('audit_chain');
            DB::setDefaultConnection('audit_chain');
            AuditLog::forgetChainLockAvailability();

            $this->write('chained');

            // Named explicitly: the Schema facade keeps the builder it
            // resolved first, which is the connection under test.
            Schema::connection('audit_chain')->drop(AuditLog::CHAIN_LOCK_TABLE);

            $row = $this->write('lock missing');

            $this->assertTrue($row->exists);
            $this->assertNull($row->record_hash);
            $this->assertSame(2, AuditLog::count());
        } finally {
            DB::purge('audit_chain');
            DB::setDefaultConnection($default);
            foreach ([$path, $path.'-wal', $path.'-shm'] as $file) {
                @unlink($file);
            }
        }
    }

    public function test_concurrent_writers_produce_one_unbroken_chain(): void
    {
        if (! function_exists('pcntl_fork')) {
            $this->markTestSkipped('pcntl is required to write from several processes.');
        }

        $workers = 4;
        $rowsEach = 25;
        $path = storage_path('framework/testing/audit-chain-'.uniqid().'.sqlite');
        $resultDir = $path.'.results';
        @mkdir(dirname($path), 0777, true);
        @mkdir($resultDir, 0777, true);
        touch($path);

        $default = config('database.default');
        config(['database.connections.audit_chain' => array_merge(config('database.connections.sqlite'), [
            'database' => $path,
            'busy_timeout' => 20000,
            'journal_mode' => 'wal',
        ])]);

        try {
            Artisan::call('migrate', ['--database' => 'audit_chain', '--path' => 'database/migrations', '--force' => true]);
            DB::purge('audit_chain');
            DB::setDefaultConnection('audit_chain');
            AuditLog::forgetChainLockAvailability();

            $children = [];
            for ($w = 0; $w < $workers; $w++) {
                $pid = pcntl_fork();
                if ($pid === 0) {
                    $this->runWorker($w, $rowsEach, $resultDir);
                }
                $children[] = $pid;
            }

            foreach ($children as $pid) {
                pcntl_waitpid($pid, $status);
            }

            DB::purge('audit_chain');

            for ($w = 0; $w < $workers; $w++) {
                $this->assertSame('ok', @file_get_contents("{$resultDir}/{$w}"), "worker {$w} did not finish");
            }

            $this->assertSame($workers * $rowsEach, AuditLog::count());
            $this->assertSame(0, AuditLog::whereNull('record_hash')->count());
            $this->assertSame($workers * $rowsEach, (int) AuditLog::max('chain_sequence'));

            $result = $this->integrity->verifyChain(updateStatus: false);
            $this->assertTrue($result['is_valid'], json_encode($result['errors']));
        } finally {
            DB::purge('audit_chain');
            DB::setDefaultConnection($default);
            foreach (glob($resultDir.'/*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($resultDir);
            foreach ([$path, $path.'-wal', $path.'-shm'] as $file) {
                @unlink($file);
            }
        }
    }

    /**
     * Child process: write rows, report, and leave without running the
     * parent's shutdown handlers.
     */
    private function runWorker(int $worker, int $rows, string $resultDir): never
    {
        try {
            DB::purge('audit_chain');

            for ($i = 0; $i < $rows; $i++) {
                $this->write("worker-{$worker}-{$i}");
            }

            file_put_contents("{$resultDir}/{$worker}", 'ok');
        } catch (\Throwable $e) {
            file_put_contents("{$resultDir}/{$worker}", $e->getMessage());
        }

        posix_kill(getmypid(), SIGKILL);
        exit(0);
    }

    private function write(string $action): AuditLog
    {
        return AuditLog::log([
            'category' => AuditLog::CATEGORY_SYSTEM,
            'action' => $action,
            'context' => ['note' => $action, 'ratio' => 0.5],
        ]);
    }

    /**
     * A row written without the chain, as an older release left it.
     */
    private function unchained(string $action): AuditLog
    {
        return AuditLog::create([
            'occurred_at' => now(),
            'severity' => AuditLog::SEVERITY_INFO,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'category' => AuditLog::CATEGORY_SYSTEM,
            'action' => $action,
            'request_id' => (string) \Illuminate\Support\Str::uuid(),
        ]);
    }
}
