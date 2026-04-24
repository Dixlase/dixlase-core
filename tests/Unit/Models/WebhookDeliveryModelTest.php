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

namespace Tests\Unit\Models;

use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookDeliveryModelTest extends TestCase
{
    use RefreshDatabase;

    private Webhook $webhook;

    protected function setUp(): void
    {
        parent::setUp();

        $this->webhook = Webhook::create([
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'secret' => Webhook::generateSecret(),
            'events' => ['test.event'],
            'is_active' => true,
            'timeout' => 30,
            'retry_count' => 3,
        ]);
    }

    private function createDelivery(array $overrides = []): WebhookDelivery
    {
        return WebhookDelivery::create(array_merge([
            'event_id' => WebhookDelivery::generateEventId(),
            'nonce' => WebhookDelivery::generateNonce(),
            'webhook_id' => $this->webhook->id,
            'event' => 'test.event',
            'payload' => ['key' => 'value'],
            'status' => WebhookDelivery::STATUS_PENDING,
            'attempt' => 1,
            'max_attempts' => 3,
        ], $overrides));
    }

    public function test_delivery_can_be_created(): void
    {
        $delivery = $this->createDelivery();

        $this->assertDatabaseHas('webhook_deliveries', ['id' => $delivery->id]);
    }

    public function test_payload_is_cast_to_array(): void
    {
        $delivery = $this->createDelivery(['payload' => ['data' => 'test']]);
        $delivery->refresh();

        $this->assertIsArray($delivery->payload);
        $this->assertEquals('test', $delivery->payload['data']);
    }

    public function test_status_constants_exist(): void
    {
        $this->assertEquals('pending', WebhookDelivery::STATUS_PENDING);
        $this->assertEquals('success', WebhookDelivery::STATUS_SUCCESS);
        $this->assertEquals('failed', WebhookDelivery::STATUS_FAILED);
        $this->assertEquals('retrying', WebhookDelivery::STATUS_RETRYING);
    }

    public function test_mark_as_success(): void
    {
        $delivery = $this->createDelivery();

        $delivery->markAsSuccess(200, '{"ok":true}', 150);
        $delivery->refresh();

        $this->assertEquals(WebhookDelivery::STATUS_SUCCESS, $delivery->status);
        $this->assertEquals(200, $delivery->response_code);
        $this->assertEquals(150, $delivery->response_time_ms);
        $this->assertNotNull($delivery->delivered_at);
    }

    public function test_mark_as_failed_with_retries_remaining(): void
    {
        $delivery = $this->createDelivery(['attempt' => 1, 'max_attempts' => 3]);

        $delivery->markAsFailed('Connection timeout', 0, null);
        $delivery->refresh();

        $this->assertEquals(WebhookDelivery::STATUS_RETRYING, $delivery->status);
        $this->assertNotNull($delivery->next_retry_at);
        $this->assertFalse($delivery->is_dead_letter);
    }

    public function test_mark_as_failed_becomes_dead_letter_at_max_attempts(): void
    {
        $delivery = $this->createDelivery(['attempt' => 3, 'max_attempts' => 3]);

        $delivery->markAsFailed('Final failure', 500, 'Server Error');
        $delivery->refresh();

        $this->assertEquals(WebhookDelivery::STATUS_FAILED, $delivery->status);
        $this->assertTrue($delivery->is_dead_letter);
        $this->assertNotNull($delivery->dead_letter_at);
    }

    public function test_can_retry(): void
    {
        $canRetry = $this->createDelivery(['attempt' => 1, 'max_attempts' => 3]);
        $this->assertTrue($canRetry->canRetry());

        $cannotRetry = $this->createDelivery(['attempt' => 3, 'max_attempts' => 3]);
        $this->assertFalse($cannotRetry->canRetry());
    }

    public function test_status_checks(): void
    {
        $delivery = $this->createDelivery(['status' => WebhookDelivery::STATUS_PENDING]);
        $this->assertTrue($delivery->isPending());

        $delivery->status = WebhookDelivery::STATUS_SUCCESS;
        $this->assertTrue($delivery->isSuccessful());

        $delivery->status = WebhookDelivery::STATUS_FAILED;
        $this->assertTrue($delivery->isFailed());
    }

    public function test_generate_event_id_is_uuid(): void
    {
        $eventId = WebhookDelivery::generateEventId();

        $this->assertNotEmpty($eventId);
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $eventId);
    }

    public function test_generate_nonce_is_hex(): void
    {
        $nonce = WebhookDelivery::generateNonce();

        $this->assertNotEmpty($nonce);
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $nonce);
    }

    public function test_webhook_relationship(): void
    {
        $delivery = $this->createDelivery();

        $this->assertNotNull($delivery->webhook);
        $this->assertEquals($this->webhook->id, $delivery->webhook->id);
    }

    public function test_pending_scope(): void
    {
        $this->createDelivery(['status' => WebhookDelivery::STATUS_PENDING]);
        $this->createDelivery(['status' => WebhookDelivery::STATUS_SUCCESS]);

        $pending = WebhookDelivery::pending()->get();

        $this->assertEquals(1, $pending->count());
    }

    public function test_increment_attempt(): void
    {
        $delivery = $this->createDelivery(['attempt' => 1]);

        $delivery->incrementAttempt();
        $delivery->refresh();

        $this->assertEquals(2, $delivery->attempt);
    }
}
