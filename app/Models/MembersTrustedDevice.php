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

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembersTrustedDevice extends Model
{
    use HasFactory;
    
    protected $table = 'members_trusted_devices';

    protected $fillable = [
        'member_id',
        'device_name',
        'token',
        'ip_address',
        'user_agent',
        'user_agent_hash',
        'trust_level',
        'first_ip',
        'last_ip',
        'last_used_at',
    ];

    /**
     * モデルの「起動」メソッド
     * user_agent設定時に自動的にハッシュを生成
     */
    protected static function booted(): void
    {
        static::saving(function (MembersTrustedDevice $device) {
            if ($device->isDirty('user_agent') && $device->user_agent) {
                $device->user_agent_hash = hash('sha256', $device->user_agent);
            }
        });
    }

    protected $casts = [
        'last_used_at' => 'datetime',
    ];

    /**
     * 信頼レベル定数
     */
    public const TRUST_LEVEL_TRUSTED = 'trusted';
    public const TRUST_LEVEL_UNKNOWN = 'unknown';
    public const TRUST_LEVEL_BLOCKED = 'blocked';

    /**
     * メンバーとのリレーション
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * 信頼済みデバイスかどうか
     */
    public function isTrusted(): bool
    {
        return $this->trust_level === self::TRUST_LEVEL_TRUSTED;
    }

    /**
     * ブロック済みデバイスかどうか
     */
    public function isBlocked(): bool
    {
        return $this->trust_level === self::TRUST_LEVEL_BLOCKED;
    }

    /**
     * 最終使用日時を更新
     */
    public function updateLastUsed(?string $ip = null): void
    {
        $this->last_used_at = now();
        if ($ip) {
            $this->last_ip = $ip;
        }
        $this->save();
    }

    /**
     * デバイスをブロック
     */
    public function block(): void
    {
        $this->trust_level = self::TRUST_LEVEL_BLOCKED;
        $this->save();
    }

    /**
     * デバイスを信頼済みに設定
     */
    public function trust(): void
    {
        $this->trust_level = self::TRUST_LEVEL_TRUSTED;
        $this->save();
    }
}
