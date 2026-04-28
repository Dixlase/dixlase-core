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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Webhook delivery log model
 *
 * @property int $id
 * @property string|null $event_id
 * @property string|null $nonce
 * @property int $webhook_id
 * @property string $event
 * @property array $payload
 * @property string $status
 * @property int|null $response_code
 * @property string|null $response_body
 * @property int|null $response_time_ms
 * @property int $attempt
 * @property int $max_attempts
 * @property string|null $error_message
 * @property bool $is_dead_letter
 * @property \Carbon\Carbon|null $dead_letter_at
 * @property bool $dead_letter_notified
 * @property \Carbon\Carbon|null $next_retry_at
 * @property \Carbon\Carbon|null $delivered_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class WebhookDelivery extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_RETRYING = 'retrying';

    protected $fillable = [
        'event_id',
        'nonce',
        'webhook_id',
        'event',
        'payload',
        'status',
        'response_code',
        'response_body',
        'response_time_ms',
        'attempt',
        'max_attempts',
        'error_message',
        'is_dead_letter',
        'dead_letter_at',
        'dead_letter_notified',
        'next_retry_at',
        'delivered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'response_code' => 'integer',
        'response_time_ms' => 'integer',
        'attempt' => 'integer',
        'max_attempts' => 'integer',
        'is_dead_letter' => 'boolean',
        'dead_letter_notified' => 'boolean',
        'dead_letter_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'delivered_at' => 'datetime',
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

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope to pending deliveries
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to successful deliveries
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    /**
     * Scope to failed deliveries
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to deliveries ready for retry
     */
    public function scopeReadyForRetry($query)
    {
        return $query->where('status', self::STATUS_RETRYING)
                     ->where('next_retry_at', '<=', now());
    }

    // =========================================================================
    // Methods
    // =========================================================================

    /**
     * Mark as successful
     */
    public function markAsSuccess(int $responseCode, ?string $responseBody, int $responseTimeMs): void
    {
        $this->update([
            'status' => self::STATUS_SUCCESS,
            'response_code' => $responseCode,
            'response_body' => $responseBody ? substr($responseBody, 0, 65535) : null,
            'response_time_ms' => $responseTimeMs,
            'delivered_at' => now(),
            'error_message' => null,
            'next_retry_at' => null,
        ]);

        $this->webhook->recordSuccess();
    }

    /**
     * Mark as failed
     *
     * @param string $errorMessage
     * @param int|null $responseCode
     * @param string|null $responseBody
     * @param array $attemptLog Optional attempt log entry for dead letter tracking
     */
    public function markAsFailed(string $errorMessage, ?int $responseCode = null, ?string $responseBody = null, array $attemptLog = []): void
    {
        $canRetry = $this->attempt < $this->max_attempts;

        $this->update([
            'status' => $canRetry ? self::STATUS_RETRYING : self::STATUS_FAILED,
            'response_code' => $responseCode,
            'response_body' => $responseBody ? substr($responseBody, 0, 65535) : null,
            'error_message' => $errorMessage,
            'next_retry_at' => $canRetry ? $this->calculateNextRetry() : null,
        ]);

        if (!$canRetry) {
            $this->webhook->recordFailure();
            $this->markAsDeadLetter();
        }
    }

    /**
     * Increment attempt counter
     */
    public function incrementAttempt(): void
    {
        $this->increment('attempt');
    }

    /**
     * Calculate next retry time using exponential backoff
     */
    protected function calculateNextRetry(): \Carbon\Carbon
    {
        // Exponential backoff: 1min, 5min, 30min
        $delays = [60, 300, 1800];
        $delayIndex = min($this->attempt - 1, count($delays) - 1);
        $delay = $delays[$delayIndex];

        return now()->addSeconds($delay);
    }

    /**
     * Check if can retry
     */
    public function canRetry(): bool
    {
        return $this->attempt < $this->max_attempts;
    }

    /**
     * Check if is successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Check if is failed
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if is pending or retrying
     */
    public function isPending(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_RETRYING], true);
    }

    /**
     * Check if is a dead letter
     */
    public function isDeadLetter(): bool
    {
        return $this->is_dead_letter;
    }

    /**
     * Mark as dead letter
     */
    public function markAsDeadLetter(): void
    {
        $this->update([
            'is_dead_letter' => true,
            'dead_letter_at' => now(),
        ]);
    }

    /**
     * Mark dead letter as notified
     */
    public function markDeadLetterNotified(): void
    {
        $this->update(['dead_letter_notified' => true]);
    }

    /**
     * Generate a unique event ID (UUID v4)
     */
    public static function generateEventId(): string
    {
        return (string) Str::uuid();
    }

    /**
     * Generate a nonce for replay prevention
     */
    public static function generateNonce(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Scope to dead letters
     */
    public function scopeDeadLetters($query)
    {
        return $query->where('is_dead_letter', true);
    }

    /**
     * Scope to unnotified dead letters
     */
    public function scopeUnnotifiedDeadLetters($query)
    {
        return $query->where('is_dead_letter', true)
                     ->where('dead_letter_notified', false);
    }

    /**
     * Get dead letter relationship
     */
    public function deadLetter()
    {
        return $this->hasOne(WebhookDeadLetter::class, 'delivery_id');
    }
}
