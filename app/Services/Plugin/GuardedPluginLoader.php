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

namespace App\Services\Plugin;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Registers and boots plugin service providers without letting one stop the application
 *
 * Core used to hand each plugin provider to Laravel and guard only the
 * register() call. Laravel boots providers afterwards, in a phase with no
 * guard, so a plugin that threw from boot() took the whole application down:
 * the site, the admin panel, and every artisan command -- which is also
 * every command an operator would reach for to recover.
 *
 * The providers are therefore driven from here instead. Each phase runs
 * inside a try, so a plugin that cannot start is skipped and the rest of the
 * application carries on.
 *
 * Throwable, not Exception: a missing class or a type error arrives as an
 * Error, and those are exactly what a plugin built against a different core
 * version produces.
 *
 * @internal Core wiring. Plugins do not call this.
 */
class GuardedPluginLoader
{
    /** @var array<class-string, array{plugin: string, provider: ServiceProvider}> */
    private array $pending = [];

    /** @var array<class-string, true> */
    private array $seen = [];

    public function __construct(private readonly Application $app) {}

    /**
     * Take a plugin's provider, register it, and queue its boot
     *
     * Core registers plugin providers from two places -- the manifest list in
     * PluginServiceProvider and the database list in PluginLoaderTrait -- and
     * the same provider can arrive from both. The first one wins; the second
     * is a no-op, which is what Laravel's own register() did for it before.
     */
    public function load(string $providerClass, string $plugin): void
    {
        if (isset($this->seen[$providerClass])) {
            return;
        }

        $provider = null;

        try {
            $provider = new $providerClass($this->app);

            if (! $provider instanceof ServiceProvider) {
                return;
            }

            $provider->register();
            $this->applyEagerBindings($provider);
        } catch (Throwable $e) {
            // Deliberately not marked as seen. Core offers this provider from
            // two places, and the earlier one runs before some core bindings
            // exist -- a plugin that cannot register yet may register fine
            // when the later path reaches it. Marking it here would turn a
            // recoverable ordering problem into a plugin that never loads.
            $this->record($plugin, $providerClass, $provider === null ? 'construct' : 'register', $e);

            return;
        }

        $this->seen[$providerClass] = true;
        $this->pending[$providerClass] = ['plugin' => $plugin, 'provider' => $provider];

        // An earlier path may have recorded this plugin as failing to
        // register. It registered now, so that record is stale.
        try {
            $this->app->make(PluginBootFailures::class)->forget($plugin);
        } catch (Throwable) {
            // Nothing to clean up if the registry is not resolvable.
        }

        // Queued after the boot phase has run -- a plugin enabled mid-request,
        // or a provider loaded by a command. Boot it now, or it never boots.
        if ($this->app->isBooted()) {
            $this->bootPending();
        }
    }

    /**
     * Boot everything registered so far that has not booted yet
     */
    public function bootPending(): void
    {
        while ($this->pending !== []) {
            $class = array_key_first($this->pending);
            ['plugin' => $plugin, 'provider' => $provider] = $this->pending[$class];
            unset($this->pending[$class]);

            try {
                // The three steps Laravel's Application::bootProvider() runs.
                $provider->callBootingCallbacks();

                if (method_exists($provider, 'boot')) {
                    $this->app->call([$provider, 'boot']);
                }

                $provider->callBootedCallbacks();
            } catch (Throwable $e) {
                $this->record($plugin, $class, 'boot', $e);
            }
        }
    }

    /**
     * Bindings and singletons declared as properties
     *
     * Laravel's Application::register() reads these off the provider. The
     * providers do not go through it any more, so they are applied here.
     */
    private function applyEagerBindings(ServiceProvider $provider): void
    {
        if (property_exists($provider, 'bindings') && is_array($provider->bindings)) {
            foreach ($provider->bindings as $abstract => $concrete) {
                $this->app->bind($abstract, $concrete);
            }
        }

        if (property_exists($provider, 'singletons') && is_array($provider->singletons)) {
            foreach ($provider->singletons as $abstract => $concrete) {
                $this->app->singleton(is_int($abstract) ? $concrete : $abstract, $concrete);
            }
        }
    }

    private function record(string $plugin, string $providerClass, string $phase, Throwable $e): void
    {
        try {
            $this->app->make(PluginBootFailures::class)->record($plugin, $providerClass, $phase, $e);
        } catch (Throwable) {
            // Reporting a broken plugin must not become a second broken thing.
        }
    }
}
