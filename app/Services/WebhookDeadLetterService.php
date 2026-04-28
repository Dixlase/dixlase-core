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

use App\Models\WebhookDeadLetter;
use App\Models\WebhookDelivery;
use Illuminate\Support\Facades\Log;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * Webhook Dead Letter Service
 *
 * Handles processing of permanently failed webhook deliveries.
 * Creates dead letter records and sends notifications.
 */
class WebhookDeadLetterService
{
    /**
     * Process a failed delivery and create dead letter record
     *
     * @param  array  $attemptLog  Optional attempt history
     */
    public static function processFailedDelivery(WebhookDelivery $delivery, array $attemptLog = []): WebhookDeadLetter
    {
        // Create dead letter record
        $deadLetter = WebhookDeadLetter::createFromDelivery($delivery, $attemptLog);

        Log::channel('admin_error')->warning('Webhook moved to dead letter queue', [
            'dead_letter_id' => $deadLetter->id,
            'webhook_id' => $delivery->webhook_id,
            'delivery_id' => $delivery->id,
            'event_id' => $delivery->event_id,
            'event' => $delivery->event,
            'total_attempts' => $delivery->attempt,
            'last_error' => $delivery->error_message,
        ]);

        return $deadLetter;
    }

    /**
     * Send notifications for unnotified dead letters
     *
     * @return int Number of notifications sent
     */
    public static function sendPendingNotifications(): int
    {
        $deadLetters = WebhookDeadLetter::unnotified()->get();
        $count = 0;

        foreach ($deadLetters as $deadLetter) {
            try {
                self::sendNotification($deadLetter);
                $deadLetter->markAsNotified();
                $count++;
            } catch (\Exception $e) {
                Log::channel('admin_error')->error('Failed to send dead letter notification', [
                    'dead_letter_id' => $deadLetter->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    /**
     * Send notification for a dead letter
     */
    protected static function sendNotification(WebhookDeadLetter $deadLetter): void
    {
        // Use SystemNotificationService if available
        if (class_exists(\App\Services\SystemNotificationService::class)) {
            $summary = $deadLetter->getSummary();

            \App\Services\SystemNotificationService::send(
                __('admin/webhook.dead_letter.notification_subject'),
                __('admin/webhook.dead_letter.notification_message', [
                    'event' => $summary['event'],
                    'webhook_name' => $summary['webhook_name'],
                    'attempts' => $summary['total_attempts'],
                    'error' => $summary['last_error'],
                ]),
                'warning'
            );
        }

        // Also mark the delivery as notified
        $deadLetter->delivery?->markDeadLetterNotified();
    }

    /**
     * Retry a dead letter manually
     *
     * @param  int|null  $memberId  Member who initiated the retry
     * @return WebhookDelivery New delivery record
     */
    public static function retryDeadLetter(WebhookDeadLetter $deadLetter, ?int $memberId = null): WebhookDelivery
    {
        if (! $deadLetter->canRetry()) {
            throw new \RuntimeException('Cannot retry: webhook is inactive');
        }

        // Create new delivery with same event_id for idempotency tracking
        $delivery = WebhookDelivery::create([
            'event_id' => $deadLetter->event_id,
            'nonce' => WebhookDelivery::generateNonce(), // New nonce for replay prevention
            'webhook_id' => $deadLetter->webhook_id,
            'event' => $deadLetter->event,
            'payload' => $deadLetter->payload,
            'status' => WebhookDelivery::STATUS_PENDING,
            'attempt' => 1,
            'max_attempts' => $deadLetter->webhook->retry_count ?? 3,
        ]);

        // Mark dead letter as manually retried
        $deadLetter->markAsManuallyRetried($memberId);

        Log::channel('admin_activity')->info('Dead letter manually retried', [
            'dead_letter_id' => $deadLetter->id,
            'new_delivery_id' => $delivery->id,
            'event_id' => $deadLetter->event_id,
            'retried_by' => $memberId,
        ]);

        return $delivery;
    }

    /**
     * Get dead letter statistics
     *
     * @param  int  $days  Number of days to look back
     */
    public static function getStats(int $days = 30): array
    {
        return WebhookDeadLetter::getStats($days);
    }

    /**
     * Cleanup old dead letters
     *
     * @param  int  $days  Delete dead letters older than this many days
     * @return int Number of records deleted
     */
    public static function cleanup(int $days = 90): int
    {
        return WebhookDeadLetter::where('created_at', '<', now()->subDays($days))->delete();
    }

    /**
     * Get recent dead letters for dashboard
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getRecent(int $limit = 10)
    {
        return WebhookDeadLetter::with(['webhook', 'delivery'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get pending (unresolved) dead letters
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getPending()
    {
        return WebhookDeadLetter::pending()
            ->with(['webhook', 'delivery'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
