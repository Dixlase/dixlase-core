<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Facades;

use App\Services\WebhookDispatcher;
use Illuminate\Support\Facades\Facade;

/**
 * Webhook Facade
 *
 * Provides a convenient static interface to the WebhookDispatcher service.
 * Plugins/themes may dispatch their own events via Webhook::dispatch().
 *
 * Usage:
 * ```php
 * use App\Facades\Webhook;
 *
 * // Dispatch to all subscribed webhooks (async)
 * Webhook::dispatch('dixlase.backup.completed', ['path' => '/backups/...']);
 *
 * // Dispatch synchronously
 * Webhook::dispatchSync('dixlase.backup.completed', $payload);
 *
 * // Get statistics
 * $stats = Webhook::getStats();
 * ```
 *
 * @method static int dispatch(string $event, array $payload, ?string $environment = null)
 * @method static array dispatchSync(string $event, array $payload, ?string $environment = null)
 * @method static \App\Models\WebhookDelivery dispatchTo(\App\Models\Webhook $webhook, string $event, array $payload)
 * @method static \App\Models\WebhookDelivery dispatchToSync(\App\Models\Webhook $webhook, string $event, array $payload)
 * @method static bool send(\App\Models\WebhookDelivery $delivery)
 * @method static bool retry(\App\Models\WebhookDelivery $delivery)
 * @method static int processRetries()
 * @method static array getStats(?int $webhookId = null, int $days = 7)
 *
 * @see \App\Services\WebhookDispatcher
 */
class Webhook extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return WebhookDispatcher::class;
    }
}
