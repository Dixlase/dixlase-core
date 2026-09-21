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

namespace App\Services;

use App\Models\AuditLog;
use App\Models\AuditLogDailySeal;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * Audit log integrity verification service
 *
 * Performs hash chain verification and creation/verification of daily signatures
 */
class AuditLogIntegrityService
{
    /**
     * Settings key for signature secret key
     */
    protected const SECRET_KEY_CONFIG = 'app.audit_log_secret';

    /**
     * Health states returned by getHealth()
     */
    public const HEALTH_OK = 'ok';

    public const HEALTH_EMPTY = 'empty';

    public const HEALTH_VERIFICATION_STALE = 'verification_stale';

    public const HEALTH_SEAL_OVERDUE = 'seal_overdue';

    public const HEALTH_CHAIN_STALLED = 'chain_stalled';

    public const HEALTH_TAMPERED = 'tampered';

    /**
     * An unchained row older than this means the hourly build is not running
     */
    public const CHAIN_STALL_HOURS = 2;

    /**
     * A completed day is expected to be sealed by this time on the next day
     * (the scheduler runs `audit:integrity seal` at 03:30; see routes/console.php)
     */
    public const SEAL_DUE_HOUR = 4;

    public const SEAL_DUE_MINUTE = 30;

    /**
     * Recommend a manual verification when some chained row has not been
     * verified within this many days
     */
    public const VERIFY_RECOMMENDED_DAYS = 30;

    /**
     * Cache key / TTL for the dashboard health snapshot (getHealthCached())
     */
    public const HEALTH_CACHE_KEY = 'audit_log_integrity_health';

    public const HEALTH_CACHE_TTL = 300;

    /**
     * Default secret key (uses APP_KEY)
     */
    protected function getSecretKey(): string
    {
        return config(self::SECRET_KEY_CONFIG) ?: config('app.key');
    }

    /**
     * Verify hash chain of logs in specified range
     *
     * @param  int|null  $fromId  Start ID (null=from beginning)
     * @param  int|null  $toId  End ID (null=to end)
     * @param  bool  $updateStatus  Whether to save verification result to DB
     * @return array Verification result
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

            // Hash verification
            if (! $log->verifyHash()) {
                $isValid = false;
                $errors[] = 'hash_mismatch';
            }

            // Chain link verification (except first record)
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
                // If first record is not genesis (only when range is not specified)
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

            // Save verification result
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
     * Set hash chain for logs of a specific day
     *
     * @return array Processing result
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
     * Set hash chain for unprocessed logs
     *
     * @param  int  $limit  Maximum number of records to process at once
     * @return array Processing result
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
     * Create daily signature (seal)
     */
    public function createDailySeal(\Carbon\Carbon $date): ?AuditLogDailySeal
    {
        // Skip if a seal already exists
        if (AuditLogDailySeal::existsForDate($date)) {
            return AuditLogDailySeal::forDate($date);
        }

        // Get logs for the day
        $logs = AuditLog::whereDate('occurred_at', $date)
            ->withHashChain()
            ->orderBy('id')
            ->get();

        if ($logs->isEmpty()) {
            return null;
        }

        $firstLog = $logs->first();
        $lastLog = $logs->last();

        // Create seal
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

        // Generate signature
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
     * Verify daily signature
     *
     * The signature, row count and final hash checks are O(1) per seal.
     * The chain check re-verifies every row of the sealed day (O(rows));
     * pass $verifyChain = false to skip it, e.g. when sweeping all seals.
     *
     * @return array Verification result
     */
    public function verifyDailySeal(\Carbon\Carbon $date, bool $verifyChain = true): array
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

        // Signature verification
        $signatureValid = $seal->verifySignature($this->getSecretKey());
        $result['checks']['signature'] = $signatureValid;

        // Log count verification
        $actualCount = AuditLog::whereDate('occurred_at', $date)->count();
        $countValid = $actualCount === $seal->log_count;
        $result['checks']['log_count'] = $countValid;

        // Final hash verification
        $lastLog = AuditLog::whereDate('occurred_at', $date)
            ->withHashChain()
            ->orderBy('id', 'desc')
            ->first();
        $hashValid = $lastLog && $lastLog->record_hash === $seal->final_hash;
        $result['checks']['final_hash'] = $hashValid;

        // Chain verification (optional, O(rows) for the day)
        $chainValid = true;
        if ($verifyChain) {
            $chainResult = $this->verifyChain($seal->first_log_id, $seal->last_log_id, false);
            $chainValid = $chainResult['is_valid'];
            $result['checks']['chain'] = $chainValid;
        }

        // Overall determination
        $result['is_valid'] = $signatureValid && $countValid && $hashValid && $chainValid;

        // Update status
        $seal->markAsVerified($result['is_valid']);
        $seal->addVerificationHistory($result['is_valid'], $result['is_valid'] ? null : json_encode($result['checks']));

        return $result;
    }

    /**
     * Verify every daily seal.
     *
     * By default each seal gets the O(1) checks only (signature, row count,
     * final hash). That catches deleted or inserted rows, a rewritten tail
     * and a tampered seal row, but not an in-place edit of an earlier row
     * whose record_hash was left untouched — only a chain pass over that
     * row detects that. Pass $verifyChain = true to re-verify the chain
     * inside every sealed day as well (O(rows)).
     *
     * @return array{total:int,valid:int,invalid:int,errors:array<int,array{date:string,seal_id:int,checks:array<string,bool>}>,is_valid:bool}
     */
    public function verifyAllSeals(bool $verifyChain = false): array
    {
        $result = [
            'total' => 0,
            'valid' => 0,
            'invalid' => 0,
            'errors' => [],
        ];

        foreach (AuditLogDailySeal::orderBy('seal_date')->cursor() as $seal) {
            $sealResult = $this->verifyDailySeal($seal->seal_date, $verifyChain);
            $result['total']++;

            if ($sealResult['is_valid']) {
                $result['valid']++;

                continue;
            }

            $result['invalid']++;
            $result['errors'][] = [
                'date' => $sealResult['date'],
                'seal_id' => $sealResult['seal_id'] ?? $seal->id,
                'checks' => $sealResult['checks'] ?? [],
            ];
        }

        $result['is_valid'] = $result['invalid'] === 0;

        return $result;
    }

    /**
     * ID of the newest record that passed chain verification, or null when
     * nothing has been verified yet. Incremental verification resumes here.
     */
    public function getLastVerifiedId(): ?int
    {
        $id = AuditLog::verified()->max('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * The standard manual verification pass: every daily seal (O(1) each)
     * plus the hash chain from the last verified record onward.
     *
     * The incremental chain pass alone would miss an edit to a row that was
     * verified earlier; the seal sweep narrows that gap but does not close
     * it (see verifyAllSeals()). Pass $fullChain = true to re-verify every
     * chained row instead — do that periodically.
     *
     * @return array{seals:array,chain:array,from_id:int|null,full_chain:bool,tampered_total:int,is_valid:bool}
     */
    public function verifyIncremental(bool $fullChain = false, bool $updateStatus = true): array
    {
        $seals = $this->verifyAllSeals(false);

        $fromId = $fullChain ? null : $this->getLastVerifiedId();
        $chain = $this->verifyChain($fromId, null, $updateStatus);

        $tamperedTotal = AuditLog::tampered()->count();

        return [
            'seals' => $seals,
            'chain' => $chain,
            'from_id' => $fromId,
            'full_chain' => $fromId === null,
            'tampered_total' => $tamperedTotal,
            'is_valid' => $seals['is_valid'] && $chain['is_valid'] && $tamperedTotal === 0,
        ];
    }

    /**
     * Create daily signatures for the past N days
     *
     * @return array Processing result
     */
    public function createPendingSeals(int $days = 7): array
    {
        $results = [];
        $today = now()->startOfDay();

        for ($i = 1; $i <= $days; $i++) {
            $date = $today->copy()->subDays($i);

            // Skip if seal already exists
            if (AuditLogDailySeal::existsForDate($date)) {
                continue;
            }

            // First build hash chain
            $this->buildChainForDate($date);

            // Create seal
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
     * Get statistics
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
     * Integrity health snapshot for the admin dashboard.
     *
     * States, from worst to best:
     * - tampered:           a chain row or a daily seal failed verification
     * - chain_stalled:      rows have waited longer than CHAIN_STALL_HOURS to
     *                       be chained (the hourly `audit:integrity build`
     *                       is not running)
     * - seal_overdue:       the most recent past day with entries has no
     *                       seal although the daily `audit:integrity seal`
     *                       run is past due (day one never trips this: a
     *                       seal only exists for completed days)
     * - verification_stale: a row chained more than VERIFY_RECOMMENDED_DAYS
     *                       ago has never been verified, or the oldest
     *                       verification is older than that (the oldest
     *                       last_verified_at is used, so an incremental pass
     *                       does not refresh this — only a pass that
     *                       re-hashed every row does). Rows chained since the
     *                       last pass are expected to be unverified and do
     *                       not count.
     * - empty:              no audit log entries yet
     * - ok
     *
     * @return array{state:string,has_entries:bool,tampered:int,invalid_seals:int,stalled:int,unsealed_date:string|null,verified_since:\Carbon\Carbon|null,last_verified_at:\Carbon\Carbon|null}
     */
    public function getHealth(): array
    {
        $now = now();
        $hasEntries = AuditLog::query()->exists();
        $tampered = AuditLog::tampered()->count();
        $invalidSeals = AuditLogDailySeal::invalid()->count();

        $stalled = AuditLog::withoutHashChain()
            ->where('created_at', '<=', $now->copy()->subHours(self::CHAIN_STALL_HOURS))
            ->count();

        $unsealedDate = null;
        $latestPastDay = AuditLog::where('occurred_at', '<', $now->copy()->startOfDay())->max('occurred_at');
        if ($latestPastDay !== null) {
            $day = \Carbon\Carbon::parse($latestPastDay)->startOfDay();
            $due = $day->copy()->addDay()->setTime(self::SEAL_DUE_HOUR, self::SEAL_DUE_MINUTE);
            if ($now->greaterThanOrEqualTo($due) && ! AuditLogDailySeal::existsForDate($day)) {
                $unsealedDate = $day->format('Y-m-d');
            }
        }

        // Staleness is judged by the OLDEST verification, so an incremental
        // pass (which bumps the newest rows only) cannot make old, un-re-hashed
        // rows look fresh. Rows chained after the last pass are expected to be
        // unverified — they only count once they have waited longer than the
        // recommended window.
        $staleCutoff = $now->copy()->subDays(self::VERIFY_RECOMMENDED_DAYS);
        $unverifiedTooLong = AuditLog::withHashChain()
            ->whereNull('last_verified_at')
            ->where('created_at', '<=', $staleCutoff)
            ->exists();
        $verifiedSinceRaw = AuditLog::withHashChain()->whereNotNull('last_verified_at')->min('last_verified_at');
        $verifiedSince = $verifiedSinceRaw === null ? null : \Carbon\Carbon::parse($verifiedSinceRaw);
        $lastVerifiedRaw = $verifiedSinceRaw === null ? null : AuditLog::withHashChain()->max('last_verified_at');
        $lastVerifiedAt = $lastVerifiedRaw === null ? null : \Carbon\Carbon::parse($lastVerifiedRaw);
        $verificationStale = $latestPastDay !== null
            && ($unverifiedTooLong || ($verifiedSince !== null && $verifiedSince->lt($staleCutoff)));

        $state = match (true) {
            $tampered > 0 || $invalidSeals > 0 => self::HEALTH_TAMPERED,
            $stalled > 0 => self::HEALTH_CHAIN_STALLED,
            $unsealedDate !== null => self::HEALTH_SEAL_OVERDUE,
            $verificationStale => self::HEALTH_VERIFICATION_STALE,
            ! $hasEntries => self::HEALTH_EMPTY,
            default => self::HEALTH_OK,
        };

        return [
            'state' => $state,
            'has_entries' => $hasEntries,
            'tampered' => $tampered,
            'invalid_seals' => $invalidSeals,
            'stalled' => $stalled,
            'unsealed_date' => $unsealedDate,
            'verified_since' => $verifiedSince,
            'last_verified_at' => $lastVerifiedAt,
        ];
    }

    /**
     * getHealth() behind a short cache. The dashboard is loaded by every
     * authenticated member and the snapshot scans the audit log table, so
     * it must not run on every request. Commands that change the state
     * call forgetHealthCache().
     */
    public function getHealthCached(): array
    {
        return Cache::remember(self::HEALTH_CACHE_KEY, self::HEALTH_CACHE_TTL, fn () => $this->getHealth());
    }

    public function forgetHealthCache(): void
    {
        Cache::forget(self::HEALTH_CACHE_KEY);
    }

    /**
     * Get logs where tampering was detected
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
