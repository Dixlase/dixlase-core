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

namespace Tests\Unit\Jobs;

use App\Jobs\SendWebhook;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Services\WebhookDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Covers the ASYNCHRONOUS webhook path.
 *
 * WebhookDispatcherTest only exercises dispatchToSync(), which calls
 * WebhookDispatcher::send() directly and never touches SendWebhook. That gap
 * let app/Jobs/SendWebhook.php stay accidentally deleted for months while the
 * suite remained green — every async dispatch fataled with
 * "Class App\Jobs\SendWebhook not found". These tests close that gap.
 */
class SendWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Webhook $webhook;

    protected function setUp(): void
    {
        parent::setUp();

        $this->webhook = Webhook::create([
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'secret' => 'whsec_testsecret1234567890abcdef',
            'events' => ['order.created'],
            'is_active' => true,
            'environment' => 'live',
            'timeout' => 30,
            'retry_count' => 3,
        ]);
    }

    public function test_dispatch_to_queues_a_send_webhook_job(): void
    {
        Queue::fake();

        $delivery = WebhookDispatcher::dispatchTo($this->webhook, 'order.created', ['id' => 1]);

        Queue::assertPushed(SendWebhook::class, function (SendWebhook $job) {
            return $job->queue === 'webhooks';
        });
        $this->assertSame(WebhookDelivery::STATUS_PENDING, $delivery->status);
    }

    public function test_event_dispatch_queues_one_job_per_subscribed_webhook(): void
    {
        $this->skipIfJsonContainsIsBrokenHere();
        Queue::fake();

        $count = WebhookDispatcher::dispatch('order.created', ['id' => 1]);

        $this->assertSame(1, $count);
        Queue::assertPushed(SendWebhook::class, 1);
    }

    public function test_event_dispatch_queues_nothing_for_an_unsubscribed_event(): void
    {
        $this->skipIfJsonContainsIsBrokenHere();
        Queue::fake();

        $count = WebhookDispatcher::dispatch('order.deleted', ['id' => 1]);

        $this->assertSame(0, $count);
        Queue::assertNotPushed(SendWebhook::class);
    }

    /**
     * Event-level dispatch goes through Webhook::subscribedTo(), which uses
     * whereJsonContains(). On SQLite that compiles to a json_each() sub-select,
     * and the connection's table prefix is wrongly applied to the virtual
     * table alias, producing "no such column: dls_json_each.value". MySQL uses
     * json_contains() with no alias, so production is unaffected — but it does
     * mean the event-level path cannot be covered by the SQLite test suite.
     *
     * Tracked separately; the dispatchTo() tests above still guard the part
     * this file exists for (that SendWebhook is present and gets queued).
     */
    private function skipIfJsonContainsIsBrokenHere(): void
    {
        if (\DB::connection()->getDriverName() === 'sqlite'
            && \DB::connection()->getTablePrefix() !== '') {
            $this->markTestSkipped(
                'whereJsonContains() is broken on prefixed SQLite (json_each alias gets the table prefix).'
            );
        }
    }

    public function test_job_delivers_a_pending_delivery(): void
    {
        Queue::fake();
        $delivery = WebhookDispatcher::dispatchTo($this->webhook, 'order.created', ['id' => 1]);

        Http::fake(['example.com/*' => Http::response('OK', 200)]);

        (new SendWebhook($delivery))->handle();

        $this->assertTrue($delivery->fresh()->isSuccessful());
        Http::assertSentCount(1);
    }

    public function test_job_records_a_failure_without_sending_when_the_webhook_is_inactive(): void
    {
        Queue::fake();
        $delivery = WebhookDispatcher::dispatchTo($this->webhook, 'order.created', ['id' => 1]);

        $this->webhook->update(['is_active' => false]);
        Http::fake();

        (new SendWebhook($delivery->fresh()))->handle();

        // markAsFailed() parks the delivery in `retrying` while attempts
        // remain (attempt 0 of max_attempts), and only flips to `failed`
        // once they are exhausted — so assert on "not delivered" plus the
        // recorded reason rather than on the terminal status.
        $fresh = $delivery->fresh();
        $this->assertFalse($fresh->isSuccessful());
        $this->assertStringContainsString('inactive', (string) $fresh->error_message);
        Http::assertNothingSent();
    }

    public function test_job_is_a_noop_when_the_delivery_already_succeeded(): void
    {
        Queue::fake();
        $delivery = WebhookDispatcher::dispatchTo($this->webhook, 'order.created', ['id' => 1]);
        $delivery->markAsSuccess(200, 'OK', 12);

        Http::fake();

        (new SendWebhook($delivery->fresh()))->handle();

        Http::assertNothingSent();
    }

    public function test_failed_hook_records_the_exception_on_the_delivery(): void
    {
        Queue::fake();
        $delivery = WebhookDispatcher::dispatchTo($this->webhook, 'order.created', ['id' => 1]);

        (new SendWebhook($delivery))->failed(new \RuntimeException('queue exploded'));

        $fresh = $delivery->fresh();
        $this->assertFalse($fresh->isSuccessful());
        $this->assertStringContainsString('queue exploded', (string) $fresh->error_message);
    }
}
