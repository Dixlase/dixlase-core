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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Audit log daily seal model
 *
 * Manages daily sealing of audit logs
 * Signs each day's log chain to enable tamper detection
 *
 * @property int $id
 * @property \Carbon\Carbon $seal_date
 * @property int $first_log_id
 * @property int $last_log_id
 * @property int $log_count
 * @property string $final_hash
 * @property string $daily_signature
 * @property int $key_version
 * @property string $signature_algorithm
 * @property string $verification_status
 * @property \Carbon\Carbon|null $last_verified_at
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AuditLogDailySeal extends Model
{
    protected $table = 'audit_log_daily_seals';

    public const STATUS_VALID = 'valid';

    public const STATUS_INVALID = 'invalid';

    public const STATUS_PENDING = 'pending';

    protected $fillable = [
        'seal_date',
        'first_log_id',
        'last_log_id',
        'log_count',
        'final_hash',
        'daily_signature',
        'key_version',
        'signature_algorithm',
        'verification_status',
        'last_verified_at',
        'metadata',
    ];

    protected $casts = [
        'seal_date' => 'date',
        'first_log_id' => 'integer',
        'last_log_id' => 'integer',
        'log_count' => 'integer',
        'key_version' => 'integer',
        'last_verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope to valid seals
     */
    public function scopeValid($query)
    {
        return $query->where('verification_status', self::STATUS_VALID);
    }

    /**
     * Scope to invalid seals
     */
    public function scopeInvalid($query)
    {
        return $query->where('verification_status', self::STATUS_INVALID);
    }

    /**
     * Scope to recent seals
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('seal_date', '>=', now()->subDays($days));
    }

    // =========================================================================
    // Methods
    // =========================================================================

    /**
     * Generate signature for this seal
     */
    public function generateSignature(string $secretKey): string
    {
        $signingString = implode('|', [
            $this->seal_date->format('Y-m-d'),
            $this->first_log_id,
            $this->last_log_id,
            $this->log_count,
            $this->final_hash,
        ]);

        return hash_hmac('sha256', $signingString, $secretKey);
    }

    /**
     * Verify this seal's signature
     */
    public function verifySignature(string $secretKey): bool
    {
        $expectedSignature = $this->generateSignature($secretKey);

        return hash_equals($expectedSignature, $this->daily_signature);
    }

    /**
     * Mark as verified
     */
    public function markAsVerified(bool $isValid): void
    {
        $this->update([
            'verification_status' => $isValid ? self::STATUS_VALID : self::STATUS_INVALID,
            'last_verified_at' => now(),
        ]);
    }

    /**
     * Add verification history to metadata
     */
    public function addVerificationHistory(bool $isValid, ?string $error = null): void
    {
        $history = $this->metadata['verification_history'] ?? [];
        $history[] = [
            'verified_at' => now()->toIso8601String(),
            'is_valid' => $isValid,
            'error' => $error,
        ];

        // Keep only last 10 entries
        $history = array_slice($history, -10);

        $this->update([
            'metadata' => array_merge($this->metadata ?? [], [
                'verification_history' => $history,
            ]),
        ]);
    }

    /**
     * Get logs for this seal date
     */
    public function getLogs()
    {
        return AuditLog::whereBetween('id', [$this->first_log_id, $this->last_log_id])
            ->orderBy('id')
            ->get();
    }

    /**
     * Check if seal exists for a date
     */
    public static function existsForDate(\Carbon\Carbon $date): bool
    {
        return self::whereDate('seal_date', $date->format('Y-m-d'))->exists();
    }

    /**
     * Get seal for a specific date
     */
    public static function forDate(\Carbon\Carbon $date): ?self
    {
        return self::whereDate('seal_date', $date->format('Y-m-d'))->first();
    }

    /**
     * Get statistics
     */
    public static function getStats(int $days = 30): array
    {
        $query = self::where('seal_date', '>=', now()->subDays($days));

        return [
            'total' => (clone $query)->count(),
            'valid' => (clone $query)->valid()->count(),
            'invalid' => (clone $query)->invalid()->count(),
            'total_logs' => (clone $query)->sum('log_count'),
        ];
    }
}
