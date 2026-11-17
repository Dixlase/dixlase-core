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

use App\Services\Security\IntegrityKeyring;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * Where audit-log retention cut the hash chain (#1048).
 *
 * Written by a retention run, under the chain lock, for the first chained
 * row it leaves. Full-chain verification accepts that row in place of the
 * genesis row only when it matches the latest point whose signature
 * verifies, so rows deleted by retention do not read as tampering, while
 * rows deleted any other way still do.
 *
 * Signed with the current integrity key, or with the legacy seal key
 * (version 1) on a site that has none yet, so every site can record one.
 *
 * @property int $id
 * @property int $first_log_id
 * @property string $previous_hash
 * @property \Carbon\Carbon|null $cutoff_at
 * @property int $deleted_rows
 * @property int $key_version
 * @property string $signature
 * @property \Carbon\Carbon|null $created_at
 */
class AuditLogRetentionPoint extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'audit_log_retention_points';

    protected $fillable = [
        'first_log_id',
        'previous_hash',
        'cutoff_at',
        'deleted_rows',
        'key_version',
        'signature',
    ];

    protected $casts = [
        'first_log_id' => 'integer',
        'cutoff_at' => 'datetime',
        'deleted_rows' => 'integer',
        'key_version' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Whether the table exists yet (before its migration has run, there
     * are no points and the chain must start at the genesis row).
     */
    public static function available(): bool
    {
        return Schema::hasTable('audit_log_retention_points');
    }

    /**
     * Record a point for the given first row, signed with the current key.
     */
    public static function record(AuditLog $firstRow, ?\Carbon\Carbon $cutoff, int $deletedRows, IntegrityKeyring $keyring): self
    {
        $current = $keyring->currentKey(IntegrityKeyring::PURPOSE_AUDIT_RETENTION)
            ?? ['version' => IntegrityKeyring::LEGACY_SEAL_VERSION, 'key' => $keyring->legacySealKey()];

        // A point signed with an empty key would never verify, and the
        // chain it was meant to vouch for would read as cut. Fail the run
        // (its transaction rolls back) instead.
        if ($current['key'] === '') {
            throw new \RuntimeException('No key to sign the audit log retention point with: set INTEGRITY_KEY or APP_KEY.');
        }

        $point = new self([
            'first_log_id' => (int) $firstRow->id,
            'previous_hash' => (string) $firstRow->previous_hash,
            'cutoff_at' => $cutoff,
            'deleted_rows' => $deletedRows,
            'key_version' => $current['version'],
        ]);
        $point->signature = $point->sign($current['key']);
        $point->save();

        return $point;
    }

    /**
     * The newest point whose signature verifies, or null.
     */
    public static function latestValid(IntegrityKeyring $keyring): ?self
    {
        if (! self::available()) {
            return null;
        }

        foreach (self::orderByDesc('id')->cursor() as $point) {
            if ($point->verifySignature($keyring)) {
                return $point;
            }
        }

        return null;
    }

    /**
     * Whether the given row is where the chain legitimately starts now.
     */
    public function anchors(AuditLog $row): bool
    {
        return $this->first_log_id === (int) $row->id
            && hash_equals($this->previous_hash, (string) $row->previous_hash);
    }

    /**
     * Whether the signature matches, using the key of this point's version.
     */
    public function verifySignature(IntegrityKeyring $keyring): bool
    {
        $key = $this->key_version === IntegrityKeyring::LEGACY_SEAL_VERSION
            ? $keyring->legacySealKey()
            : $keyring->key(IntegrityKeyring::PURPOSE_AUDIT_RETENTION, $this->key_version);

        return $key !== null && $key !== ''
            && hash_equals($this->sign($key), (string) $this->signature);
    }

    private function sign(string $key): string
    {
        return hash_hmac('sha256', implode('|', [
            'dixlase-audit-retention',
            $this->first_log_id,
            $this->previous_hash,
            $this->cutoff_at?->format('Y-m-d H:i:s') ?? '',
            $this->deleted_rows,
            $this->key_version,
        ]), $key);
    }
}
