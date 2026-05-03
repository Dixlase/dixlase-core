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

namespace App\Services;

use App\Jobs\SendWebhook;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Support\DixlaseSigner;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * @internal Core use only. Do not reference from plugins/themes
 *
 * Webhook Dispatcher Service
 *
 * Dispatches webhooks to registered endpoints with signature verification.
 * Supports both synchronous and asynchronous (queued) delivery.
 *
 * Usage:
 * ```php
 * // Dispatch to all subscribed webhooks (async)
 * WebhookDispatcher::dispatch('dixlase.backup.completed', ['path' => '/backups/...']);
 *
 * // Dispatch synchronously
 * WebhookDispatcher::dispatchSync('dixlase.backup.completed', $payload);
 *
 * // Dispatch to specific webhook
 * WebhookDispatcher::dispatchTo($webhook, 'event.name', $payload);
 * ```
 *
 * @see docs/webhook-spec.md
 */
class WebhookDispatcher
{
    /**
     * Dispatch webhook to all subscribed endpoints (async via queue)
     *
     * @param  string  $event  Event name (e.g., 'dixlase.backup.completed')
     * @param  array  $payload  Event payload data
     * @param  string|null  $environment  Filter by environment (live/test), null for all
     * @return int Number of webhooks dispatched
     */
    public static function dispatch(string $event, array $payload, ?string $environment = null): int
    {
        $webhooks = self::getSubscribedWebhooks($event, $environment);
        $count = 0;

        foreach ($webhooks as $webhook) {
            $delivery = self::createDelivery($webhook, $event, $payload);
            SendWebhook::dispatch($delivery);
            $count++;
        }

        if ($count > 0) {
            Log::channel('admin_activity')->info('Webhook dispatched', [
                'event' => $event,
                'webhook_count' => $count,
            ]);
        }

        return $count;
    }

    /**
     * Dispatch webhook synchronously (blocking)
     *
     * @param  string  $event  Event name
     * @param  array  $payload  Event payload data
     * @param  string|null  $environment  Filter by environment
     * @return array Results keyed by webhook ID
     */
    public static function dispatchSync(string $event, array $payload, ?string $environment = null): array
    {
        $webhooks = self::getSubscribedWebhooks($event, $environment);
        $results = [];

        foreach ($webhooks as $webhook) {
            $delivery = self::createDelivery($webhook, $event, $payload);
            $results[$webhook->id] = self::send($delivery);
        }

        return $results;
    }

    /**
     * Dispatch to a specific webhook (async)
     *
     * @param  Webhook  $webhook  Target webhook
     * @param  string  $event  Event name
     * @param  array  $payload  Event payload data
     */
    public static function dispatchTo(Webhook $webhook, string $event, array $payload): WebhookDelivery
    {
        $delivery = self::createDelivery($webhook, $event, $payload);
        SendWebhook::dispatch($delivery);

        return $delivery;
    }

    /**
     * Dispatch to a specific webhook synchronously
     *
     * @param  Webhook  $webhook  Target webhook
     * @param  string  $event  Event name
     * @param  array  $payload  Event payload data
     */
    public static function dispatchToSync(Webhook $webhook, string $event, array $payload): WebhookDelivery
    {
        $delivery = self::createDelivery($webhook, $event, $payload);
        self::send($delivery);

        return $delivery;
    }

    /**
     * Send a webhook delivery
     *
     * @return bool Success status
     */
    public static function send(WebhookDelivery $delivery): bool
    {
        $webhook = $delivery->webhook;
        $payload = $delivery->payload;

        // Build request body
        $body = json_encode([
            'event' => $delivery->event,
            'timestamp' => now()->unix(),
            'data' => $payload,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Generate signature
        $timestamp = time();
        $path = parse_url($webhook->url, PHP_URL_PATH) ?: '/';
        $signatureHeaders = DixlaseSigner::sign('POST', $path, $body, $webhook->secret, $timestamp);

        // Build headers
        $headers = array_merge(
            [
                'Content-Type' => 'application/json',
                'User-Agent' => 'Dixlase-Webhook/1.0',
                'X-Dixlase-Event' => $delivery->event,
                'X-Dixlase-Delivery' => (string) $delivery->id,
                'X-Dixlase-Event-Id' => $delivery->event_id ?? '',
                'X-Dixlase-Nonce' => $delivery->nonce ?? '',
            ],
            $signatureHeaders,
            $webhook->headers ?? []
        );

        $startTime = microtime(true);

        try {
            $response = Http::withHeaders($headers)
                ->timeout($webhook->timeout)
                ->withBody($body, 'application/json')
                ->post($webhook->url);

            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $delivery->markAsSuccess(
                    $response->status(),
                    $response->body(),
                    $responseTimeMs
                );

                Log::channel('admin_activity')->info('Webhook delivered', [
                    'webhook_id' => $webhook->id,
                    'delivery_id' => $delivery->id,
                    'event' => $delivery->event,
                    'response_code' => $response->status(),
                    'response_time_ms' => $responseTimeMs,
                ]);

                return true;
            }

            // Non-2xx response
            $delivery->markAsFailed(
                "HTTP {$response->status()}: ".substr($response->body(), 0, 500),
                $response->status(),
                $response->body()
            );

            Log::channel('admin_error')->warning('Webhook delivery failed', [
                'webhook_id' => $webhook->id,
                'delivery_id' => $delivery->id,
                'event' => $delivery->event,
                'response_code' => $response->status(),
                'attempt' => $delivery->attempt,
            ]);

            return false;
        } catch (\Exception $e) {
            $delivery->markAsFailed($e->getMessage());

            Log::channel('admin_error')->error('Webhook delivery error', [
                'webhook_id' => $webhook->id,
                'delivery_id' => $delivery->id,
                'event' => $delivery->event,
                'error' => $e->getMessage(),
                'attempt' => $delivery->attempt,
            ]);

            return false;
        }
    }

    /**
     * Retry a failed delivery
     *
     * @return bool Success status
     */
    public static function retry(WebhookDelivery $delivery): bool
    {
        if (! $delivery->canRetry()) {
            return false;
        }

        $delivery->incrementAttempt();

        return self::send($delivery);
    }

    /**
     * Process all pending retries
     *
     * @return int Number of retries processed
     */
    public static function processRetries(): int
    {
        $deliveries = WebhookDelivery::readyForRetry()->get();
        $count = 0;

        foreach ($deliveries as $delivery) {
            SendWebhook::dispatch($delivery);
            $count++;
        }

        return $count;
    }

    /**
     * Get webhooks subscribed to an event
     *
     * @param  string  $event  Event name
     * @param  string|null  $environment  Filter by environment
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected static function getSubscribedWebhooks(string $event, ?string $environment = null)
    {
        $query = Webhook::active()->subscribedTo($event);

        if ($environment !== null) {
            $query->environment($environment);
        }

        return $query->get();
    }

    /**
     * Create a delivery record
     *
     * @param  string|null  $eventId  Optional event ID for idempotency (auto-generated if null)
     */
    protected static function createDelivery(Webhook $webhook, string $event, array $payload, ?string $eventId = null): WebhookDelivery
    {
        return WebhookDelivery::create([
            'event_id' => $eventId ?? WebhookDelivery::generateEventId(),
            'nonce' => WebhookDelivery::generateNonce(),
            'webhook_id' => $webhook->id,
            'event' => $event,
            'payload' => $payload,
            'status' => WebhookDelivery::STATUS_PENDING,
            'attempt' => 1,
            'max_attempts' => $webhook->retry_count,
        ]);
    }

    /**
     * Get delivery statistics
     *
     * @param  int|null  $webhookId  Filter by webhook ID
     * @param  int  $days  Number of days to look back
     */
    public static function getStats(?int $webhookId = null, int $days = 7): array
    {
        $query = WebhookDelivery::where('created_at', '>=', now()->subDays($days));

        if ($webhookId !== null) {
            $query->where('webhook_id', $webhookId);
        }

        $total = $query->count();
        $successful = (clone $query)->successful()->count();
        $failed = (clone $query)->failed()->count();
        $pending = (clone $query)->where(function ($q) {
            $q->pending()->orWhere('status', WebhookDelivery::STATUS_RETRYING);
        })->count();

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'pending' => $pending,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 100.0,
        ];
    }
}
