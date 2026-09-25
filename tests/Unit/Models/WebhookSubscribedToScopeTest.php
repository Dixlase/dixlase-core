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

namespace Tests\Unit\Models;

use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Which webhooks a dispatch actually selects.
 *
 * On SQLite the scope used to throw outright: Laravel renders
 * `whereJsonContains` as `... where "json_each"."value" is ?` and wraps
 * `json_each.value` as a qualified column, so a prefixed connection —
 * Dixlase ships `dls_` — asked for `dls_json_each.value`. The listener
 * only logs the failure, so a SQLite site delivered no webhooks at all
 * and said nothing on screen.
 *
 * The suite runs on SQLite with that prefix, so these tests reproduce the
 * original failure as an exception rather than a wrong result set.
 */
class WebhookSubscribedToScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_webhook_is_selected_for_an_event_it_subscribes_to(): void
    {
        $webhook = $this->createWebhook(['events' => ['post.created', 'post.updated']]);

        $this->assertSame([$webhook->id], $this->subscribedIds('post.created'));
        $this->assertSame([$webhook->id], $this->subscribedIds('post.updated'));
    }

    public function test_a_webhook_is_not_selected_for_other_events(): void
    {
        $this->createWebhook(['events' => ['post.created']]);

        $this->assertSame([], $this->subscribedIds('member.deleted'));
    }

    public function test_a_wildcard_subscription_takes_every_event(): void
    {
        $webhook = $this->createWebhook(['events' => ['*']]);

        $this->assertSame([$webhook->id], $this->subscribedIds('anything.at.all'));
    }

    public function test_a_null_event_list_takes_every_event(): void
    {
        $webhook = $this->createWebhook(['events' => null]);

        $this->assertSame([$webhook->id], $this->subscribedIds('anything.at.all'));
    }

    public function test_only_the_matching_webhooks_come_back(): void
    {
        $matching = $this->createWebhook(['name' => 'Matching', 'events' => ['post.created']]);
        $wildcard = $this->createWebhook(['name' => 'Wildcard', 'events' => ['*']]);
        $catchAll = $this->createWebhook(['name' => 'Catch all', 'events' => null]);
        $this->createWebhook(['name' => 'Unrelated', 'events' => ['member.created']]);

        $this->assertSame(
            [$matching->id, $wildcard->id, $catchAll->id],
            $this->subscribedIds('post.created')
        );
    }

    public function test_the_query_does_not_reference_a_prefixed_json_each(): void
    {
        // The exact shape of the original failure: a table prefix applied
        // to SQLite's json_each virtual table.
        $sql = Webhook::query()->subscribedTo('post.created')->toSql();

        $this->assertStringNotContainsString('json_each"."value', $sql);
        $this->assertStringNotContainsString(config('database.connections.sqlite.prefix').'json_each', $sql);
    }

    /**
     * @return array<int, int>
     */
    private function subscribedIds(string $event): array
    {
        return Webhook::query()->subscribedTo($event)->orderBy('id')->pluck('id')->all();
    }

    private function createWebhook(array $overrides = []): Webhook
    {
        return Webhook::create(array_merge([
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'secret' => Webhook::generateSecret(),
            'events' => ['order.created'],
            'is_active' => true,
            'environment' => 'live',
            'timeout' => 30,
            'retry_count' => 3,
        ], $overrides));
    }
}
