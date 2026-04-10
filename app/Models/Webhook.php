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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Webhook registration model
 *
 * @property int $id
 * @property string $name
 * @property string $url
 * @property string $secret
 * @property array|null $events
 * @property bool $is_active
 * @property string $environment
 * @property int $timeout
 * @property int $retry_count
 * @property array|null $headers
 * @property string|null $description
 * @property \Carbon\Carbon|null $last_triggered_at
 * @property int $success_count
 * @property int $failure_count
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Webhook extends Model
{
    protected $fillable = [
        'name',
        'url',
        'secret',
        'events',
        'is_active',
        'environment',
        'timeout',
        'retry_count',
        'headers',
        'description',
    ];

    protected $casts = [
        'events' => 'array',
        'headers' => 'array',
        'is_active' => 'boolean',
        'timeout' => 'integer',
        'retry_count' => 'integer',
        'success_count' => 'integer',
        'failure_count' => 'integer',
        'last_triggered_at' => 'datetime',
    ];

    protected $hidden = [
        'secret',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get webhook deliveries
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope to active webhooks
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to webhooks subscribed to an event
     */
    public function scopeSubscribedTo($query, string $event)
    {
        return $query->where(function ($q) use ($event) {
            // events が null の場合は全イベントを購読
            $q->whereNull('events')
                ->orWhereJsonContains('events', $event)
                ->orWhereJsonContains('events', '*');
        });
    }

    /**
     * Scope by environment
     */
    public function scopeEnvironment($query, string $environment)
    {
        return $query->where('environment', $environment);
    }

    // =========================================================================
    // Methods
    // =========================================================================

    /**
     * Check if webhook is subscribed to an event
     */
    public function isSubscribedTo(string $event): bool
    {
        if ($this->events === null || in_array('*', $this->events, true)) {
            return true;
        }

        return in_array($event, $this->events, true);
    }

    /**
     * Generate a new secret
     */
    public static function generateSecret(): string
    {
        return 'whsec_'.Str::random(48);
    }

    /**
     * Record successful delivery
     */
    public function recordSuccess(): void
    {
        $this->increment('success_count', 1, ['last_triggered_at' => now()]);
    }

    /**
     * Record failed delivery
     */
    public function recordFailure(): void
    {
        $this->increment('failure_count', 1, ['last_triggered_at' => now()]);
    }

    /**
     * Get success rate
     */
    public function getSuccessRate(): float
    {
        $total = $this->success_count + $this->failure_count;
        if ($total === 0) {
            return 100.0;
        }

        return round(($this->success_count / $total) * 100, 2);
    }

    /**
     * Get masked secret for display
     */
    public function getMaskedSecret(): string
    {
        if (strlen($this->secret) <= 12) {
            return str_repeat('*', strlen($this->secret));
        }

        return substr($this->secret, 0, 8).'...'.substr($this->secret, -4);
    }
}
