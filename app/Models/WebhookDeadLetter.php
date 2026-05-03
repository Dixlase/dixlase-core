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
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
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

namespace App\Models;

use App\Models\Traits\BelongsToSite;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Webhook Dead Letter model
 *
 * Records permanently failed webhook deliveries for manual review and retry.
 *
 * @property int $id
 * @property int $webhook_id
 * @property int $delivery_id
 * @property string $event_id
 * @property string $event
 * @property array $payload
 * @property array|null $request_headers
 * @property string $last_error
 * @property int $total_attempts
 * @property array|null $attempt_log
 * @property \Carbon\Carbon $first_attempt_at
 * @property \Carbon\Carbon $last_attempt_at
 * @property bool $notified
 * @property \Carbon\Carbon|null $notified_at
 * @property bool $manually_retried
 * @property \Carbon\Carbon|null $manually_retried_at
 * @property int|null $manually_retried_by
 * @property string|null $resolution_notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class WebhookDeadLetter extends Model
{
    use BelongsToSite;

    protected $fillable = [
        'site_id',
        'webhook_id',
        'delivery_id',
        'event_id',
        'event',
        'payload',
        'request_headers',
        'last_error',
        'total_attempts',
        'attempt_log',
        'first_attempt_at',
        'last_attempt_at',
        'notified',
        'notified_at',
        'manually_retried',
        'manually_retried_at',
        'manually_retried_by',
        'resolution_notes',
    ];

    protected $casts = [
        'payload' => 'array',
        'request_headers' => 'array',
        'attempt_log' => 'array',
        'total_attempts' => 'integer',
        'notified' => 'boolean',
        'manually_retried' => 'boolean',
        'first_attempt_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'notified_at' => 'datetime',
        'manually_retried_at' => 'datetime',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the webhook
     */
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    /**
     * Get the original delivery
     */
    public function delivery(): BelongsTo
    {
        return $this->belongsTo(WebhookDelivery::class, 'delivery_id');
    }

    /**
     * Get the member who manually retried
     */
    public function retriedBy(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'manually_retried_by');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope to unnotified dead letters
     */
    public function scopeUnnotified($query)
    {
        return $query->where('notified', false);
    }

    /**
     * Scope to pending (not manually retried) dead letters
     */
    public function scopePending($query)
    {
        return $query->where('manually_retried', false);
    }

    /**
     * Scope to recent dead letters
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope by event type
     */
    public function scopeForEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    // =========================================================================
    // Methods
    // =========================================================================

    /**
     * Mark as notified
     */
    public function markAsNotified(): void
    {
        $this->update([
            'notified' => true,
            'notified_at' => now(),
        ]);
    }

    /**
     * Mark as manually retried
     */
    public function markAsManuallyRetried(?int $memberId = null): void
    {
        $this->update([
            'manually_retried' => true,
            'manually_retried_at' => now(),
            'manually_retried_by' => $memberId,
        ]);
    }

    /**
     * Add resolution notes
     */
    public function addResolutionNotes(string $notes): void
    {
        $this->update(['resolution_notes' => $notes]);
    }

    /**
     * Get formatted attempt log for display
     */
    public function getFormattedAttemptLog(): array
    {
        if (empty($this->attempt_log)) {
            return [];
        }

        return array_map(function ($attempt) {
            return [
                'attempt' => $attempt['attempt'] ?? 0,
                'timestamp' => isset($attempt['timestamp'])
                    ? \Carbon\Carbon::parse($attempt['timestamp'])->format('Y-m-d H:i:s')
                    : null,
                'status_code' => $attempt['status_code'] ?? null,
                'error' => $attempt['error'] ?? null,
                'response_time_ms' => $attempt['response_time_ms'] ?? null,
            ];
        }, $this->attempt_log);
    }

    /**
     * Check if can be retried
     */
    public function canRetry(): bool
    {
        // Can retry if webhook is still active
        return $this->webhook && $this->webhook->is_active;
    }

    /**
     * Get summary for notifications
     */
    public function getSummary(): array
    {
        return [
            'id' => $this->id,
            'event_id' => $this->event_id,
            'event' => $this->event,
            'webhook_name' => $this->webhook->name ?? 'Unknown',
            'webhook_url' => $this->webhook->url ?? 'Unknown',
            'total_attempts' => $this->total_attempts,
            'last_error' => $this->last_error,
            'first_attempt_at' => $this->first_attempt_at?->format('Y-m-d H:i:s'),
            'last_attempt_at' => $this->last_attempt_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Create from a failed delivery
     */
    public static function createFromDelivery(WebhookDelivery $delivery, array $attemptLog = []): self
    {
        return self::create([
            'webhook_id' => $delivery->webhook_id,
            'delivery_id' => $delivery->id,
            'event_id' => $delivery->event_id,
            'event' => $delivery->event,
            'payload' => $delivery->payload,
            'last_error' => $delivery->error_message,
            'total_attempts' => $delivery->attempt,
            'attempt_log' => $attemptLog,
            'first_attempt_at' => $delivery->created_at,
            'last_attempt_at' => now(),
        ]);
    }

    /**
     * Get statistics
     */
    public static function getStats(int $days = 30): array
    {
        $query = self::where('created_at', '>=', now()->subDays($days));

        return [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->pending()->count(),
            'notified' => (clone $query)->where('notified', true)->count(),
            'manually_retried' => (clone $query)->where('manually_retried', true)->count(),
            'by_event' => (clone $query)
                ->selectRaw('event, COUNT(*) as count')
                ->groupBy('event')
                ->pluck('count', 'event')
                ->toArray(),
        ];
    }
}
