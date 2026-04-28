<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

namespace App\Services;

use App\Models\AuditLog;
use App\Models\AuditLogDailySeal;
use Illuminate\Support\Facades\Log;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * 監査ログ整合性検証サービス
 *
 * ハッシュチェーンの検証、日次署名の作成・検証を行う
 */
class AuditLogIntegrityService
{
    /**
     * 署名用シークレットキーの設定キー
     */
    protected const SECRET_KEY_CONFIG = 'app.audit_log_secret';

    /**
     * デフォルトのシークレットキー（APP_KEYを使用）
     */
    protected function getSecretKey(): string
    {
        return config(self::SECRET_KEY_CONFIG) ?: config('app.key');
    }

    /**
     * 指定範囲のログのハッシュチェーンを検証
     *
     * @param  int|null  $fromId  開始ID（null=最初から）
     * @param  int|null  $toId  終了ID（null=最後まで）
     * @param  bool  $updateStatus  検証結果をDBに保存するか
     * @return array 検証結果
     */
    public function verifyChain(?int $fromId = null, ?int $toId = null, bool $updateStatus = true): array
    {
        $query = AuditLog::withHashChain()->orderBy('id');

        if ($fromId !== null) {
            $query->where('id', '>=', $fromId);
        }
        if ($toId !== null) {
            $query->where('id', '<=', $toId);
        }

        $logs = $query->get();

        $result = [
            'total' => $logs->count(),
            'valid' => 0,
            'invalid' => 0,
            'errors' => [],
            'first_id' => $logs->first()?->id,
            'last_id' => $logs->last()?->id,
        ];

        $previousHash = null;
        $previousSequence = 0;

        foreach ($logs as $log) {
            $isValid = true;
            $errors = [];

            // ハッシュ検証
            if (! $log->verifyHash()) {
                $isValid = false;
                $errors[] = 'hash_mismatch';
            }

            // チェーンリンク検証（最初のレコード以外）
            if ($previousHash !== null) {
                if ($log->previous_hash !== $previousHash) {
                    $isValid = false;
                    $errors[] = 'chain_broken';
                }
                if ($log->chain_sequence !== $previousSequence + 1) {
                    $isValid = false;
                    $errors[] = 'sequence_gap';
                }
            } elseif ($log->previous_hash !== AuditLog::GENESIS_HASH && $fromId === null) {
                // 最初のレコードでgenesis以外の場合（範囲指定なしの場合のみ）
                $isValid = false;
                $errors[] = 'invalid_genesis';
            }

            if ($isValid) {
                $result['valid']++;
            } else {
                $result['invalid']++;
                $result['errors'][] = [
                    'id' => $log->id,
                    'errors' => $errors,
                ];
            }

            // 検証結果を保存
            if ($updateStatus) {
                $log->markAsVerified($isValid);
            }

            $previousHash = $log->record_hash;
            $previousSequence = $log->chain_sequence;
        }

        $result['is_valid'] = $result['invalid'] === 0;

        return $result;
    }

    /**
     * 特定の日のログにハッシュチェーンを設定
     *
     * @return array 処理結果
     */
    public function buildChainForDate(\Carbon\Carbon $date): array
    {
        $logs = AuditLog::whereDate('occurred_at', $date)
            ->whereNull('record_hash')
            ->orderBy('id')
            ->get();

        $processed = 0;
        $errors = [];

        foreach ($logs as $log) {
            try {
                $log->saveWithHashChain();
                $processed++;
            } catch (\Exception $e) {
                $errors[] = [
                    'id' => $log->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'date' => $date->format('Y-m-d'),
            'processed' => $processed,
            'errors' => $errors,
        ];
    }

    /**
     * 未処理のログにハッシュチェーンを設定
     *
     * @param  int  $limit  一度に処理する最大件数
     * @return array 処理結果
     */
    public function buildPendingChains(int $limit = 1000): array
    {
        $logs = AuditLog::whereNull('record_hash')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $processed = 0;
        $errors = [];

        foreach ($logs as $log) {
            try {
                $log->saveWithHashChain();
                $processed++;
            } catch (\Exception $e) {
                $errors[] = [
                    'id' => $log->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $remaining = AuditLog::whereNull('record_hash')->count();

        return [
            'processed' => $processed,
            'remaining' => $remaining,
            'errors' => $errors,
        ];
    }

    /**
     * 日次署名（シール）を作成
     */
    public function createDailySeal(\Carbon\Carbon $date): ?AuditLogDailySeal
    {
        // 既にシールが存在する場合はスキップ
        if (AuditLogDailySeal::existsForDate($date)) {
            return AuditLogDailySeal::forDate($date);
        }

        // その日のログを取得
        $logs = AuditLog::whereDate('occurred_at', $date)
            ->withHashChain()
            ->orderBy('id')
            ->get();

        if ($logs->isEmpty()) {
            return null;
        }

        $firstLog = $logs->first();
        $lastLog = $logs->last();

        // シールを作成
        $seal = new AuditLogDailySeal([
            'seal_date' => $date,
            'first_log_id' => $firstLog->id,
            'last_log_id' => $lastLog->id,
            'log_count' => $logs->count(),
            'final_hash' => $lastLog->record_hash,
            'key_version' => 1,
            'signature_algorithm' => 'hmac-sha256',
            'verification_status' => AuditLogDailySeal::STATUS_VALID,
        ]);

        // 署名を生成
        $seal->daily_signature = $seal->generateSignature($this->getSecretKey());
        $seal->save();

        Log::channel('admin_activity')->info('Daily audit log seal created', [
            'date' => $date->format('Y-m-d'),
            'log_count' => $logs->count(),
            'first_id' => $firstLog->id,
            'last_id' => $lastLog->id,
        ]);

        return $seal;
    }

    /**
     * 日次署名を検証
     *
     * @return array 検証結果
     */
    public function verifyDailySeal(\Carbon\Carbon $date): array
    {
        $seal = AuditLogDailySeal::forDate($date);

        if (! $seal) {
            return [
                'date' => $date->format('Y-m-d'),
                'exists' => false,
                'is_valid' => false,
                'error' => 'seal_not_found',
            ];
        }

        $result = [
            'date' => $date->format('Y-m-d'),
            'exists' => true,
            'seal_id' => $seal->id,
            'log_count' => $seal->log_count,
            'checks' => [],
        ];

        // 署名検証
        $signatureValid = $seal->verifySignature($this->getSecretKey());
        $result['checks']['signature'] = $signatureValid;

        // ログ件数検証
        $actualCount = AuditLog::whereDate('occurred_at', $date)->count();
        $countValid = $actualCount === $seal->log_count;
        $result['checks']['log_count'] = $countValid;

        // 最終ハッシュ検証
        $lastLog = AuditLog::whereDate('occurred_at', $date)
            ->withHashChain()
            ->orderBy('id', 'desc')
            ->first();
        $hashValid = $lastLog && $lastLog->record_hash === $seal->final_hash;
        $result['checks']['final_hash'] = $hashValid;

        // チェーン検証
        $chainResult = $this->verifyChain($seal->first_log_id, $seal->last_log_id, false);
        $result['checks']['chain'] = $chainResult['is_valid'];

        // 総合判定
        $result['is_valid'] = $signatureValid && $countValid && $hashValid && $chainResult['is_valid'];

        // ステータス更新
        $seal->markAsVerified($result['is_valid']);
        $seal->addVerificationHistory($result['is_valid'], $result['is_valid'] ? null : json_encode($result['checks']));

        return $result;
    }

    /**
     * 過去N日分の日次署名を作成
     *
     * @return array 処理結果
     */
    public function createPendingSeals(int $days = 7): array
    {
        $results = [];
        $today = now()->startOfDay();

        for ($i = 1; $i <= $days; $i++) {
            $date = $today->copy()->subDays($i);

            // 既にシールがある場合はスキップ
            if (AuditLogDailySeal::existsForDate($date)) {
                continue;
            }

            // まずハッシュチェーンを構築
            $this->buildChainForDate($date);

            // シールを作成
            $seal = $this->createDailySeal($date);

            if ($seal) {
                $results[] = [
                    'date' => $date->format('Y-m-d'),
                    'log_count' => $seal->log_count,
                    'status' => 'created',
                ];
            }
        }

        return $results;
    }

    /**
     * 統計情報を取得
     */
    public function getStats(): array
    {
        return [
            'total_logs' => AuditLog::count(),
            'with_hash_chain' => AuditLog::withHashChain()->count(),
            'without_hash_chain' => AuditLog::withoutHashChain()->count(),
            'verified' => AuditLog::verified()->count(),
            'tampered' => AuditLog::tampered()->count(),
            'unverified' => AuditLog::unverified()->count(),
            'daily_seals' => AuditLogDailySeal::getStats(),
        ];
    }

    /**
     * 改ざんが検知されたログを取得
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTamperedLogs(int $limit = 100)
    {
        return AuditLog::tampered()
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();
    }
}
