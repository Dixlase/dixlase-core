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

namespace Tests\Unit\Services;

use App\Models\Webhook;
use App\Models\WebhookDeadLetter;
use App\Models\WebhookDelivery;
use App\Services\WebhookDeadLetterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookDeadLetterServiceTest extends TestCase
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
            'timeout' => 30,
            'retry_count' => 3,
        ]);
    }

    private function createFailedDelivery(): WebhookDelivery
    {
        return WebhookDelivery::create([
            'event_id' => WebhookDelivery::generateEventId(),
            'nonce' => WebhookDelivery::generateNonce(),
            'webhook_id' => $this->webhook->id,
            'event' => 'order.created',
            'payload' => ['order_id' => 123],
            'status' => WebhookDelivery::STATUS_FAILED,
            'attempt' => 3,
            'max_attempts' => 3,
            'error_message' => 'Connection refused',
            'is_dead_letter' => true,
            'dead_letter_at' => now(),
        ]);
    }

    public function test_process_failed_delivery_creates_dead_letter(): void
    {
        $delivery = $this->createFailedDelivery();

        $deadLetter = WebhookDeadLetterService::processFailedDelivery($delivery);

        $this->assertInstanceOf(WebhookDeadLetter::class, $deadLetter);
        $this->assertDatabaseHas('webhook_dead_letters', [
            'webhook_id' => $this->webhook->id,
            'event' => 'order.created',
        ]);
    }

    public function test_process_failed_delivery_preserves_event_id(): void
    {
        $delivery = $this->createFailedDelivery();
        $eventId = $delivery->event_id;

        $deadLetter = WebhookDeadLetterService::processFailedDelivery($delivery);

        $this->assertEquals($eventId, $deadLetter->event_id);
    }

    public function test_process_failed_delivery_records_error(): void
    {
        $delivery = $this->createFailedDelivery();

        $deadLetter = WebhookDeadLetterService::processFailedDelivery($delivery);

        $this->assertEquals('Connection refused', $deadLetter->last_error);
    }

    public function test_retry_dead_letter_creates_new_delivery(): void
    {
        $delivery = $this->createFailedDelivery();
        $deadLetter = WebhookDeadLetterService::processFailedDelivery($delivery);

        $newDelivery = WebhookDeadLetterService::retryDeadLetter($deadLetter);

        $this->assertInstanceOf(WebhookDelivery::class, $newDelivery);
        $this->assertEquals($delivery->event_id, $newDelivery->event_id);
        $this->assertNotEquals($delivery->nonce, $newDelivery->nonce);
    }

    public function test_retry_dead_letter_marks_as_retried(): void
    {
        $delivery = $this->createFailedDelivery();
        $deadLetter = WebhookDeadLetterService::processFailedDelivery($delivery);

        WebhookDeadLetterService::retryDeadLetter($deadLetter, 42);

        $deadLetter->refresh();
        $this->assertTrue($deadLetter->manually_retried);
        $this->assertEquals(42, $deadLetter->manually_retried_by);
    }

    public function test_retry_dead_letter_throws_for_inactive_webhook(): void
    {
        $delivery = $this->createFailedDelivery();
        $deadLetter = WebhookDeadLetterService::processFailedDelivery($delivery);

        $this->webhook->update(['is_active' => false]);

        $this->expectException(\RuntimeException::class);
        WebhookDeadLetterService::retryDeadLetter($deadLetter);
    }

    public function test_cleanup_returns_integer(): void
    {
        $deleted = WebhookDeadLetterService::cleanup(90);

        $this->assertIsInt($deleted);
        $this->assertEquals(0, $deleted);
    }

    public function test_cleanup_keeps_recent_dead_letters(): void
    {
        $delivery = $this->createFailedDelivery();
        WebhookDeadLetterService::processFailedDelivery($delivery);

        $deleted = WebhookDeadLetterService::cleanup(90);

        $this->assertEquals(0, $deleted);
    }

    public function test_get_stats_returns_expected_keys(): void
    {
        $stats = WebhookDeadLetterService::getStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('pending', $stats);
    }

    public function test_get_recent_returns_collection(): void
    {
        $recent = WebhookDeadLetterService::getRecent();

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $recent);
    }

    public function test_get_pending_returns_unresolved(): void
    {
        $delivery = $this->createFailedDelivery();
        $deadLetter = WebhookDeadLetterService::processFailedDelivery($delivery);

        $pending = WebhookDeadLetterService::getPending();

        $this->assertEquals(1, $pending->count());
    }
}
