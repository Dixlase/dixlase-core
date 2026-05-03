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

namespace App\Providers;

use App\Events\DixlaseEvents;
use App\Listeners\WebhookEventListener;
use App\Services\WebhookDispatcher;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Webhook Service Provider
 *
 * Registers webhook services and event listeners.
 */
class WebhookServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register WebhookDispatcher as singleton
        $this->app->singleton(WebhookDispatcher::class, function ($app) {
            return new WebhookDispatcher();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register event listeners for all Dixlase events
        $this->registerEventListeners();
    }

    /**
     * Register event listeners for webhook dispatching
     */
    protected function registerEventListeners(): void
    {
        // Get all Dixlase events
        $events = DixlaseEvents::all();

        // Register listener for each event
        foreach ($events as $event) {
            Event::listen($event, function ($payload = []) use ($event) {
                // Ensure payload is an array
                if (! is_array($payload)) {
                    $payload = ['data' => $payload];
                }

                // Dispatch webhook
                app(WebhookEventListener::class)->handle($event, $payload);
            });
        }
    }
}
