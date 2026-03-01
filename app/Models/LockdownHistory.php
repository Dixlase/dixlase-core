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

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ロックダウン履歴モデル
 *
 * @property int $id
 * @property string $action
 * @property string $type
 * @property string|null $reason
 * @property int|null $performed_by
 * @property string|null $ip_address
 * @property array|null $details
 * @property \Carbon\Carbon $performed_at
 */
class LockdownHistory extends Model
{
    protected $table = 'lockdown_history';

    public $timestamps = false;

    // アクションタイプ
    public const ACTION_ACTIVATED = 'activated';
    public const ACTION_DEACTIVATED = 'deactivated';
    public const ACTION_EXTENDED = 'extended';
    public const ACTION_MODIFIED = 'modified';
    public const ACTION_AUTO_RELEASED = 'auto_released';

    protected $fillable = [
        'action',
        'type',
        'reason',
        'performed_by',
        'ip_address',
        'details',
        'performed_at',
    ];

    protected $casts = [
        'details' => 'array',
        'performed_at' => 'datetime',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function performedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'performed_by');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('performed_at', '>=', now()->subDays($days));
    }

    public function scopeOfAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    // =========================================================================
    // Static Methods
    // =========================================================================

    /**
     * 履歴を記録
     */
    public static function record(
        string $action,
        string $type,
        ?string $reason = null,
        ?int $performedBy = null,
        ?string $ipAddress = null,
        ?array $details = null
    ): self {
        return self::create([
            'action' => $action,
            'type' => $type,
            'reason' => $reason,
            'performed_by' => $performedBy,
            'ip_address' => $ipAddress ?? request()->ip(),
            'details' => $details,
            'performed_at' => now(),
        ]);
    }

    /**
     * アクションのラベルを取得
     */
    public function getActionLabel(): string
    {
        return match ($this->action) {
            self::ACTION_ACTIVATED => __('admin/lockdown.actions.activated'),
            self::ACTION_DEACTIVATED => __('admin/lockdown.actions.deactivated'),
            self::ACTION_EXTENDED => __('admin/lockdown.actions.extended'),
            self::ACTION_MODIFIED => __('admin/lockdown.actions.modified'),
            self::ACTION_AUTO_RELEASED => __('admin/lockdown.actions.auto_released'),
            default => $this->action,
        };
    }
}
