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
use App\Models\AuditLogDailySeal;
use App\Models\AuditLogKeyEpoch;
use App\Services\AuditLogIntegrityService;
use App\Services\Security\IntegrityKeyring;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Core #665: the per-row hash chain is keyed with the integrity key, and the
 * chain key and the seal key can be rotated without what was signed before
 * failing verification.
 */
class KeyedHashChainTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogIntegrityService $integrity;

    protected function setUp(): void
    {
        parent::setUp();

        AuditLog::forgetChainLockAvailability();
        $this->useKeys(null);
        $this->integrity = app(AuditLogIntegrityService::class);
    }

    protected function tearDown(): void
    {
        AuditLog::forgetChainLockAvailability();

        parent::tearDown();
    }

    public function test_a_row_is_keyed_with_the_current_key_and_starts_its_epoch(): void
    {
        $this->useKeys(IntegrityKeyring::generate(2));

        $first = $this->write('first');
        $second = $this->write('second');

        $this->assertSame(2, $first->hash_key_version);
        $this->assertSame(AuditLog::HASH_ALGORITHM_KEYED, $first->hash_algorithm);
        $this->assertNotSame($first->calculateHash(null, IntegrityKeyring::UNKEYED_VERSION), $first->record_hash);

        $epoch = AuditLogKeyEpoch::sole();
        $this->assertSame(2, $epoch->key_version);
        $this->assertSame($first->id, $epoch->first_log_id);

        $result = $this->integrity->verifyChain();
        $this->assertTrue($result['is_valid'], json_encode($result['errors']));
        $this->assertSame(2, $result['valid']);
        $this->assertSame($first->record_hash, $second->previous_hash);
    }

    public function test_without_a_key_rows_stay_unkeyed_as_before(): void
    {
        $row = $this->write('unkeyed');

        $this->assertSame(0, $row->fresh()->hash_key_version);
        $this->assertSame(AuditLog::HASH_ALGORITHM, $row->hash_algorithm);
        $this->assertSame(0, AuditLogKeyEpoch::count());
        $this->assertTrue($this->integrity->verifyChain()['is_valid']);
    }

    public function test_rows_written_before_the_key_keep_verifying_after_it(): void
    {
        $old = $this->write('before keying');
        $this->useKeys(IntegrityKeyring::generate(2));
        $new = $this->write('after keying');

        $this->assertSame(0, $old->fresh()->hash_key_version);
        $this->assertSame(2, $new->hash_key_version);
        $this->assertSame($old->record_hash, $new->previous_hash);

        $result = $this->integrity->verifyChain();
        $this->assertTrue($result['is_valid'], json_encode($result['errors']));
    }

    public function test_a_rewrite_without_the_key_is_detected(): void
    {
        $this->useKeys(IntegrityKeyring::generate(2));
        $row = $this->write('original');

        // Rewrite the row and recompute what a key-less attacker can: a
        // plain SHA-256 under the row's own key version.
        DB::table('audit_logs')->where('id', $row->id)->update(['action' => 'rewritten']);
        $rewritten = $row->fresh();
        $forged = hash('sha256', implode('|', [$rewritten->id, $rewritten->occurred_at->toIso8601String(), $rewritten->severity, $rewritten->outcome, $rewritten->category, $rewritten->action, $rewritten->actor_type ?? '', $rewritten->actor_id ?? '', $rewritten->target_type ?? '', $rewritten->target_id ?? '', $rewritten->ip_address ?? '', json_encode($rewritten->context ?? []), $rewritten->previous_hash]));
        DB::table('audit_logs')->where('id', $row->id)->update(['record_hash' => $forged]);

        $result = $this->integrity->verifyChain();
        $this->assertFalse($result['is_valid']);
        $this->assertContains('hash_mismatch', $result['errors'][0]['errors']);
    }

    public function test_a_keyed_row_rewritten_as_an_unkeyed_one_is_detected(): void
    {
        $this->useKeys(IntegrityKeyring::generate(2));
        $row = $this->write('original');

        DB::table('audit_logs')->where('id', $row->id)->update(['action' => 'rewritten', 'hash_key_version' => 0]);
        $rewritten = $row->fresh();
        DB::table('audit_logs')->where('id', $row->id)->update([
            'record_hash' => $rewritten->calculateHash($rewritten->previous_hash, IntegrityKeyring::UNKEYED_VERSION),
            'hash_algorithm' => AuditLog::HASH_ALGORITHM,
        ]);

        $result = $this->integrity->verifyChain();
        $this->assertFalse($result['is_valid']);
        $this->assertSame(['unkeyed_after_keying'], $result['errors'][0]['errors']);
    }

    public function test_a_keyed_row_without_its_epoch_is_detected(): void
    {
        $this->useKeys(IntegrityKeyring::generate(2));
        $this->write('keyed');

        AuditLogKeyEpoch::query()->delete();

        $result = $this->integrity->verifyChain();
        $this->assertFalse($result['is_valid']);
        $this->assertSame(['key_epoch_missing'], $result['errors'][0]['errors']);
    }

    public function test_an_altered_epoch_fails_verification(): void
    {
        $this->useKeys(IntegrityKeyring::generate(2));
        $first = $this->write('first');
        $second = $this->write('second');

        // Moving the start of keying past the first row would let it be
        // rewritten unkeyed; the epoch's signature no longer matches.
        AuditLogKeyEpoch::query()->update(['first_log_id' => $second->id]);

        $result = $this->integrity->verifyChain();
        $this->assertFalse($result['is_valid']);
        $this->assertSame(1, $result['invalid_epochs']);
        $this->assertSame($first->id, $result['errors'][0]['id']);
        $this->assertSame(['key_epoch_missing'], $result['errors'][0]['errors']);
    }

    public function test_rotation_keeps_earlier_rows_verifying_with_the_previous_key(): void
    {
        $v2 = IntegrityKeyring::generate(2);
        $this->useKeys($v2);
        $before = $this->write('signed with 2');

        $this->useKeys(IntegrityKeyring::generate(3), $v2);
        $after = $this->write('signed with 3');

        $this->assertSame(2, $before->fresh()->hash_key_version);
        $this->assertSame(3, $after->hash_key_version);
        $this->assertSame([2, 3], AuditLogKeyEpoch::orderBy('key_version')->pluck('key_version')->all());

        $result = $this->integrity->verifyChain();
        $this->assertTrue($result['is_valid'], json_encode($result['errors']));
        $this->assertSame(2, $result['valid']);
    }

    public function test_a_row_whose_key_was_removed_fails_with_key_unavailable(): void
    {
        $v2 = IntegrityKeyring::generate(2);
        $this->useKeys($v2);
        $before = $this->write('signed with 2');
        $this->useKeys(IntegrityKeyring::generate(3), $v2);
        $this->write('signed with 3');

        $this->useKeys(config('security.integrity_keys.current'));

        $result = $this->integrity->verifyChain();
        $this->assertFalse($result['is_valid']);
        $this->assertSame(1, $result['invalid_epochs']);
        $this->assertSame($before->id, $result['errors'][0]['id']);
        $this->assertContains('key_unavailable', $result['errors'][0]['errors']);
        $this->assertNotContains('hash_mismatch', $result['errors'][0]['errors']);
    }

    public function test_a_seal_made_before_keying_keeps_its_legacy_key_and_new_seals_use_the_integrity_key(): void
    {
        config(['app.audit_log_secret' => 'legacy-secret']);
        $twoDaysAgo = now()->subDays(2)->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        $this->writeAt('two days ago', $twoDaysAgo->copy()->addHour());
        $legacy = $this->integrity->createDailySeal($twoDaysAgo);
        $this->assertSame(IntegrityKeyring::LEGACY_SEAL_VERSION, $legacy->key_version);

        $v2 = IntegrityKeyring::generate(2);
        $this->useKeys($v2);
        $this->writeAt('yesterday', $yesterday->copy()->addHour());
        $keyed = $this->integrity->createDailySeal($yesterday);
        $this->assertSame(2, $keyed->key_version);

        // A rotation keeps both verifying.
        $this->useKeys(IntegrityKeyring::generate(3), $v2);

        $this->assertTrue($this->integrity->verifyDailySeal($twoDaysAgo)['is_valid']);
        $this->assertTrue($this->integrity->verifyDailySeal($yesterday)['is_valid']);
    }

    public function test_a_seal_whose_key_was_removed_reports_it(): void
    {
        $this->useKeys(IntegrityKeyring::generate(2));
        $yesterday = now()->subDay()->startOfDay();
        $this->writeAt('yesterday', $yesterday->copy()->addHour());
        $this->integrity->createDailySeal($yesterday);

        $this->useKeys(IntegrityKeyring::generate(3));

        $result = $this->integrity->verifyDailySeal($yesterday, false);
        $this->assertFalse($result['is_valid']);
        $this->assertFalse($result['checks']['key_available']);
        $this->assertFalse($result['checks']['signature']);
    }

    public function test_a_seal_re_signed_with_the_legacy_key_version_does_not_verify(): void
    {
        $this->useKeys(IntegrityKeyring::generate(2));
        $yesterday = now()->subDay()->startOfDay();
        $this->writeAt('yesterday', $yesterday->copy()->addHour());
        $this->integrity->createDailySeal($yesterday);

        AuditLogDailySeal::query()->update(['key_version' => IntegrityKeyring::LEGACY_SEAL_VERSION]);

        $this->assertFalse($this->integrity->verifyDailySeal($yesterday, false)['checks']['signature']);
    }

    public function test_health_reports_whether_the_chain_is_keyed(): void
    {
        $this->assertFalse($this->integrity->getHealth()['keyed']);

        $this->useKeys(IntegrityKeyring::generate(2));
        $this->assertTrue($this->integrity->getHealth()['keyed']);
    }

    private function useKeys(?string $current, ?string $previous = null): void
    {
        config([
            'security.integrity_keys.current' => $current,
            'security.integrity_keys.previous' => $previous,
        ]);
    }

    private function write(string $action): AuditLog
    {
        return AuditLog::log([
            'category' => AuditLog::CATEGORY_SYSTEM,
            'action' => $action,
            'context' => ['note' => $action],
        ]);
    }

    private function writeAt(string $action, \Carbon\Carbon $at): AuditLog
    {
        $log = AuditLog::create([
            'occurred_at' => $at,
            'severity' => AuditLog::SEVERITY_INFO,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'category' => AuditLog::CATEGORY_SYSTEM,
            'action' => $action,
            'ip_address' => '127.0.0.1',
            'context' => [],
        ]);
        $log->saveWithHashChain();

        return $log;
    }
}
