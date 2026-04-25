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

use App\Models\AuditLog;
use App\Models\AuditLogDailySeal;
use App\Services\AuditLogIntegrityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 監査ログ完全性テスト
 *
 * α版リリース前の必須チェック項目:
 * - 改ざん（1行変更/削除/挿入）で検知できる
 * - 欠損（途中抜け）を検知できる
 * - 日次署名の検証が機能する
 */
class AuditLogIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected AuditLogIntegrityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuditLogIntegrityService();
    }

    // ========================================
    // ハッシュチェーン基本テスト
    // ========================================

    public function test_can_create_audit_log_with_hash_chain(): void
    {
        $log = $this->createAuditLog();
        $log->saveWithHashChain();

        $this->assertNotNull($log->record_hash);
        $this->assertEquals(AuditLog::GENESIS_HASH, $log->previous_hash);
        $this->assertEquals(1, $log->chain_sequence);
        $this->assertEquals(AuditLog::HASH_ALGORITHM, $log->hash_algorithm);
    }

    public function test_hash_chain_links_correctly(): void
    {
        $log1 = $this->createAuditLog(['action' => 'login']);
        $log1->saveWithHashChain();

        $log2 = $this->createAuditLog(['action' => 'logout']);
        $log2->saveWithHashChain();

        $this->assertEquals($log1->record_hash, $log2->previous_hash);
        $this->assertEquals($log1->chain_sequence + 1, $log2->chain_sequence);
    }

    public function test_verify_hash_returns_true_for_valid_log(): void
    {
        $log = $this->createAuditLog();
        $log->saveWithHashChain();

        $this->assertTrue($log->verifyHash());
    }

    // ========================================
    // 改ざん検知テスト（1行変更）
    // ========================================

    public function test_detects_tampered_action_field(): void
    {
        $log = $this->createAuditLog(['action' => 'login']);
        $log->saveWithHashChain();

        // 直接DBを更新して改ざんをシミュレート
        AuditLog::where('id', $log->id)->update(['action' => 'logout']);
        $log->refresh();

        $this->assertFalse($log->verifyHash(), 'Should detect tampered action field');
    }

    public function test_detects_tampered_severity_field(): void
    {
        $log = $this->createAuditLog(['severity' => AuditLog::SEVERITY_INFO]);
        $log->saveWithHashChain();

        AuditLog::where('id', $log->id)->update(['severity' => AuditLog::SEVERITY_CRITICAL]);
        $log->refresh();

        $this->assertFalse($log->verifyHash(), 'Should detect tampered severity field');
    }

    public function test_detects_tampered_ip_address(): void
    {
        $log = $this->createAuditLog(['ip_address' => '192.168.1.1']);
        $log->saveWithHashChain();

        AuditLog::where('id', $log->id)->update(['ip_address' => '10.0.0.1']);
        $log->refresh();

        $this->assertFalse($log->verifyHash(), 'Should detect tampered IP address');
    }

    public function test_detects_tampered_context(): void
    {
        $log = $this->createAuditLog(['context' => ['original' => 'data']]);
        $log->saveWithHashChain();

        AuditLog::where('id', $log->id)->update(['context' => json_encode(['tampered' => 'data'])]);
        $log->refresh();

        $this->assertFalse($log->verifyHash(), 'Should detect tampered context');
    }

    public function test_detects_tampered_actor_id(): void
    {
        $log = $this->createAuditLog(['actor_id' => 1]);
        $log->saveWithHashChain();

        AuditLog::where('id', $log->id)->update(['actor_id' => 999]);
        $log->refresh();

        $this->assertFalse($log->verifyHash(), 'Should detect tampered actor_id');
    }

    public function test_detects_tampered_occurred_at(): void
    {
        $log = $this->createAuditLog(['occurred_at' => now()]);
        $log->saveWithHashChain();

        AuditLog::where('id', $log->id)->update(['occurred_at' => now()->subDays(30)]);
        $log->refresh();

        $this->assertFalse($log->verifyHash(), 'Should detect tampered occurred_at');
    }

    // ========================================
    // チェーン破壊検知テスト
    // ========================================

    public function test_detects_broken_chain_link(): void
    {
        $log1 = $this->createAuditLog(['action' => 'login']);
        $log1->saveWithHashChain();

        $log2 = $this->createAuditLog(['action' => 'logout']);
        $log2->saveWithHashChain();

        // previous_hashを改ざん
        AuditLog::where('id', $log2->id)->update(['previous_hash' => 'fake_hash']);

        $result = $this->service->verifyChain(null, null, false);

        $this->assertFalse($result['is_valid'], 'Should detect broken chain link');
        $this->assertGreaterThan(0, $result['invalid']);
        $this->assertContains('chain_broken', $result['errors'][0]['errors'] ?? []);
    }

    public function test_detects_tampered_record_hash(): void
    {
        $log1 = $this->createAuditLog(['action' => 'login']);
        $log1->saveWithHashChain();

        $log2 = $this->createAuditLog(['action' => 'logout']);
        $log2->saveWithHashChain();

        // log1のrecord_hashを改ざん（log2のprevious_hashとの不一致を発生させる）
        AuditLog::where('id', $log1->id)->update(['record_hash' => 'tampered_hash']);

        $result = $this->service->verifyChain(null, null, false);

        $this->assertFalse($result['is_valid'], 'Should detect tampered record_hash');
    }

    // ========================================
    // 欠損検知テスト（途中抜け）
    // ========================================

    public function test_detects_sequence_gap(): void
    {
        $log1 = $this->createAuditLog(['action' => 'login']);
        $log1->saveWithHashChain();

        $log2 = $this->createAuditLog(['action' => 'view']);
        $log2->saveWithHashChain();

        $log3 = $this->createAuditLog(['action' => 'logout']);
        $log3->saveWithHashChain();

        // log2を削除して欠損をシミュレート
        AuditLog::where('id', $log2->id)->delete();

        $result = $this->service->verifyChain(null, null, false);

        $this->assertFalse($result['is_valid'], 'Should detect sequence gap');

        // chain_brokenまたはsequence_gapエラーが含まれることを確認
        $hasGapError = false;
        foreach ($result['errors'] as $error) {
            if (in_array('chain_broken', $error['errors']) || in_array('sequence_gap', $error['errors'])) {
                $hasGapError = true;
                break;
            }
        }
        $this->assertTrue($hasGapError, 'Should report chain_broken or sequence_gap error');
    }

    public function test_detects_missing_first_record(): void
    {
        $log1 = $this->createAuditLog(['action' => 'login']);
        $log1->saveWithHashChain();

        $log2 = $this->createAuditLog(['action' => 'logout']);
        $log2->saveWithHashChain();

        // 最初のログを削除
        AuditLog::where('id', $log1->id)->delete();

        $result = $this->service->verifyChain(null, null, false);

        $this->assertFalse($result['is_valid'], 'Should detect missing first record');
        $this->assertContains('invalid_genesis', $result['errors'][0]['errors'] ?? []);
    }

    // ========================================
    // 挿入検知テスト
    // ========================================

    public function test_detects_inserted_record(): void
    {
        $log1 = $this->createAuditLog(['action' => 'login']);
        $log1->saveWithHashChain();

        $log2 = $this->createAuditLog(['action' => 'logout']);
        $log2->saveWithHashChain();

        // 不正なレコードを挿入（ハッシュチェーンを無視）
        $fakeLog = AuditLog::create([
            'occurred_at' => now(),
            'severity' => AuditLog::SEVERITY_INFO,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'category' => AuditLog::CATEGORY_AUTH,
            'action' => 'fake_action',
            'record_hash' => 'fake_hash',
            'previous_hash' => $log1->record_hash,
            'chain_sequence' => 2,
            'hash_algorithm' => AuditLog::HASH_ALGORITHM,
        ]);

        $result = $this->service->verifyChain(null, null, false);

        $this->assertFalse($result['is_valid'], 'Should detect inserted record with invalid hash');
    }

    // ========================================
    // 日次署名（シール）テスト
    // ========================================

    public function test_can_create_daily_seal(): void
    {
        $yesterday = now()->subDay()->startOfDay();

        // 昨日のログを作成
        $log1 = $this->createAuditLog(['occurred_at' => $yesterday]);
        $log1->saveWithHashChain();

        $log2 = $this->createAuditLog(['occurred_at' => $yesterday->copy()->addHours(2)]);
        $log2->saveWithHashChain();

        $seal = $this->service->createDailySeal($yesterday);

        $this->assertNotNull($seal);
        $this->assertEquals($yesterday->format('Y-m-d'), $seal->seal_date->format('Y-m-d'));
        $this->assertEquals(2, $seal->log_count);
        $this->assertEquals($log1->id, $seal->first_log_id);
        $this->assertEquals($log2->id, $seal->last_log_id);
        $this->assertEquals($log2->record_hash, $seal->final_hash);
        $this->assertNotEmpty($seal->daily_signature);
    }

    public function test_daily_seal_verification_passes_for_valid_logs(): void
    {
        $yesterday = now()->subDay()->startOfDay();

        $log1 = $this->createAuditLog(['occurred_at' => $yesterday]);
        $log1->saveWithHashChain();

        $log2 = $this->createAuditLog(['occurred_at' => $yesterday->copy()->addHours(2)]);
        $log2->saveWithHashChain();

        $this->service->createDailySeal($yesterday);

        $result = $this->service->verifyDailySeal($yesterday);

        $this->assertTrue($result['is_valid'], 'Daily seal verification should pass for valid logs');
        $this->assertTrue($result['checks']['signature']);
        $this->assertTrue($result['checks']['log_count']);
        $this->assertTrue($result['checks']['final_hash']);
        $this->assertTrue($result['checks']['chain']);
    }

    public function test_daily_seal_detects_tampered_log(): void
    {
        $yesterday = now()->subDay()->startOfDay();

        $log1 = $this->createAuditLog(['occurred_at' => $yesterday, 'action' => 'login']);
        $log1->saveWithHashChain();

        $this->service->createDailySeal($yesterday);

        // ログを改ざん
        AuditLog::where('id', $log1->id)->update(['action' => 'tampered']);

        $result = $this->service->verifyDailySeal($yesterday);

        $this->assertFalse($result['is_valid'], 'Daily seal should detect tampered log');
        $this->assertFalse($result['checks']['chain'] ?? true);
    }

    public function test_daily_seal_detects_added_log(): void
    {
        $yesterday = now()->subDay()->startOfDay();

        $log1 = $this->createAuditLog(['occurred_at' => $yesterday]);
        $log1->saveWithHashChain();

        $this->service->createDailySeal($yesterday);

        // シール作成後に新しいログを追加（同じ日付）
        $log2 = $this->createAuditLog(['occurred_at' => $yesterday->copy()->addHours(5)]);
        $log2->saveWithHashChain();

        $result = $this->service->verifyDailySeal($yesterday);

        $this->assertFalse($result['is_valid'], 'Daily seal should detect added log');
        $this->assertFalse($result['checks']['log_count']);
    }

    public function test_daily_seal_detects_deleted_log(): void
    {
        $yesterday = now()->subDay()->startOfDay();

        $log1 = $this->createAuditLog(['occurred_at' => $yesterday]);
        $log1->saveWithHashChain();

        $log2 = $this->createAuditLog(['occurred_at' => $yesterday->copy()->addHours(2)]);
        $log2->saveWithHashChain();

        $this->service->createDailySeal($yesterday);

        // ログを削除
        AuditLog::where('id', $log2->id)->delete();

        $result = $this->service->verifyDailySeal($yesterday);

        $this->assertFalse($result['is_valid'], 'Daily seal should detect deleted log');
        $this->assertFalse($result['checks']['log_count']);
    }

    public function test_daily_seal_detects_signature_tampering(): void
    {
        $yesterday = now()->subDay()->startOfDay();

        $log1 = $this->createAuditLog(['occurred_at' => $yesterday]);
        $log1->saveWithHashChain();

        $seal = $this->service->createDailySeal($yesterday);

        // 署名を改ざん
        AuditLogDailySeal::where('id', $seal->id)->update(['daily_signature' => 'tampered_signature']);

        $result = $this->service->verifyDailySeal($yesterday);

        $this->assertFalse($result['is_valid'], 'Should detect tampered signature');
        $this->assertFalse($result['checks']['signature']);
    }

    // ========================================
    // 統合テスト
    // ========================================

    public function test_verify_chain_service_detects_all_tampering_types(): void
    {
        // 正常なチェーンを作成
        $logs = [];
        for ($i = 0; $i < 5; $i++) {
            $log = $this->createAuditLog(['action' => "action_{$i}"]);
            $log->saveWithHashChain();
            $logs[] = $log;
        }

        // 検証が通ることを確認
        $result = $this->service->verifyChain(null, null, false);
        $this->assertTrue($result['is_valid'], 'Initial chain should be valid');

        // 中間のログを改ざん
        AuditLog::where('id', $logs[2]->id)->update(['action' => 'tampered']);

        $result = $this->service->verifyChain(null, null, false);
        $this->assertFalse($result['is_valid'], 'Should detect tampering in middle of chain');
        $this->assertGreaterThan(0, count($result['errors']));
    }

    public function test_get_stats_returns_correct_counts(): void
    {
        // ログを作成
        $log1 = $this->createAuditLog();
        $log1->saveWithHashChain();

        $log2 = $this->createAuditLog();
        $log2->saveWithHashChain();

        // 検証を実行
        $this->service->verifyChain(null, null, true);

        $stats = $this->service->getStats();

        $this->assertEquals(2, $stats['total_logs']);
        $this->assertEquals(2, $stats['with_hash_chain']);
        $this->assertEquals(0, $stats['without_hash_chain']);
        $this->assertEquals(2, $stats['verified']);
        $this->assertEquals(0, $stats['tampered']);
    }

    public function test_get_tampered_logs_returns_invalid_records(): void
    {
        $log1 = $this->createAuditLog(['action' => 'login']);
        $log1->saveWithHashChain();

        $log2 = $this->createAuditLog(['action' => 'logout']);
        $log2->saveWithHashChain();

        // log1を改ざん
        AuditLog::where('id', $log1->id)->update(['action' => 'tampered']);

        // 検証を実行（ステータスを更新）
        $this->service->verifyChain(null, null, true);

        $tamperedLogs = $this->service->getTamperedLogs();

        $this->assertGreaterThan(0, $tamperedLogs->count());
        $this->assertTrue($tamperedLogs->contains('id', $log1->id));
    }

    // ========================================
    // ヘルパーメソッド
    // ========================================

    protected function createAuditLog(array $attributes = []): AuditLog
    {
        return AuditLog::create(array_merge([
            'occurred_at' => now(),
            'severity' => AuditLog::SEVERITY_INFO,
            'outcome' => AuditLog::OUTCOME_SUCCESS,
            'category' => AuditLog::CATEGORY_AUTH,
            'action' => AuditLog::ACTION_LOGIN,
            'ip_address' => '127.0.0.1',
            'context' => [],
        ], $attributes));
    }
}
