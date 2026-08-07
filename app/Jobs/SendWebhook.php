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

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Services\WebhookDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job to send a webhook delivery.
 *
 * This job performs the actual HTTP request to the webhook endpoint. It is
 * what makes `WebhookDispatcher::dispatch()`, `dispatchTo()` and
 * `processRetries()` asynchronous — the synchronous counterparts
 * (`dispatchSync()` / `dispatchToSync()`) call `WebhookDispatcher::send()`
 * directly and never reach this class.
 *
 * Delivery attempts are counted and rescheduled by WebhookDispatcher, not by
 * the queue, hence `$tries = 1`: a failure here must not be retried by the
 * worker or the attempt bookkeeping would double-count.
 */
class SendWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1; // Retries are handled by WebhookDispatcher

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 60;

    /**
     * The webhook delivery to send.
     */
    protected WebhookDelivery $delivery;

    /**
     * Create a new job instance.
     */
    public function __construct(WebhookDelivery $delivery)
    {
        $this->delivery = $delivery;
        $this->onQueue('webhooks');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Skip if already delivered or permanently failed
        if ($this->delivery->isSuccessful() || $this->delivery->isFailed()) {
            return;
        }

        // Skip if webhook is inactive
        if (! $this->delivery->webhook->is_active) {
            $this->delivery->markAsFailed('Webhook is inactive');

            return;
        }

        WebhookDispatcher::send($this->delivery);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->delivery->markAsFailed('Job failed: '.$exception->getMessage());
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'webhook',
            'webhook:'.$this->delivery->webhook_id,
            'event:'.$this->delivery->event,
        ];
    }
}
