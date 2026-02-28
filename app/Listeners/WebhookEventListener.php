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

declare(strict_types=1);

namespace App\Listeners;

use App\Services\WebhookDispatcher;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Event Listener
 *
 * Listens to Dixlase events and dispatches webhooks to subscribed endpoints.
 * This listener is registered in WebhookServiceProvider.
 */
class WebhookEventListener
{
    /**
     * Handle the event.
     *
     * @param  string  $eventName  The event name
     * @param  array  $payload  The event payload
     */
    public function handle(string $eventName, array $payload = []): void
    {
        try {
            WebhookDispatcher::dispatch($eventName, $payload);
        } catch (\Exception $e) {
            Log::channel('admin_error')->error('Failed to dispatch webhook', [
                'event' => $eventName,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
