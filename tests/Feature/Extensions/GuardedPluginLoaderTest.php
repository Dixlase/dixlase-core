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

namespace Tests\Feature\Extensions;

use App\Services\Plugin\GuardedPluginLoader;
use App\Services\Plugin\PluginBootFailures;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Tests\TestCase;

/**
 * A plugin that cannot start must not take the application with it.
 *
 * Core guarded `$this->app->register($provider)`, which runs a provider's
 * register(). Laravel boots providers afterwards, outside that guard, so a
 * plugin throwing from boot() stopped everything -- the site, the admin
 * panel, and every artisan command, which is also every command an operator
 * would reach for to recover. The guard was one phase too early.
 *
 * The providers are driven by GuardedPluginLoader instead of being handed to
 * Laravel, because Laravel keys registered providers by class name: wrapping
 * each one in a shared wrapper class would make every plugin after the first
 * look already-registered and silently drop it.
 */
class GuardedPluginLoaderTest extends TestCase
{
    private function loader(): GuardedPluginLoader
    {
        $this->app->forgetInstance(GuardedPluginLoader::class);
        $this->app->forgetInstance(PluginBootFailures::class);
        $this->app->singleton(PluginBootFailures::class);
        $this->app->singleton(GuardedPluginLoader::class);

        return $this->app->make(GuardedPluginLoader::class);
    }

    private function failures(): array
    {
        return $this->app->make(PluginBootFailures::class)->all();
    }

    public function test_a_provider_that_throws_from_boot_does_not_escape(): void
    {
        $loader = $this->loader();

        $loader->load(ThrowsFromBootProvider::class, 'ThrowingPlugin');
        $loader->bootPending();

        $this->assertSame('boot', $this->failures()['ThrowingPlugin']['phase'] ?? null);
    }

    public function test_a_provider_that_throws_from_register_does_not_escape(): void
    {
        $loader = $this->loader();

        $loader->load(ThrowsFromRegisterProvider::class, 'ThrowingPlugin');
        $loader->bootPending();

        $this->assertSame('register', $this->failures()['ThrowingPlugin']['phase'] ?? null);
    }

    /**
     * An Error is not an Exception. A missing class and a type error both
     * arrive as Error, and those are what a plugin built against a different
     * core version produces -- the case this exists for.
     */
    public function test_an_error_is_caught_as_well_as_an_exception(): void
    {
        $loader = $this->loader();

        $loader->load(ThrowsErrorFromBootProvider::class, 'ErrorPlugin');
        $loader->bootPending();

        $this->assertSame('boot', $this->failures()['ErrorPlugin']['phase'] ?? null);
    }

    public function test_one_broken_plugin_does_not_stop_the_others(): void
    {
        $loader = $this->loader();

        $loader->load(ThrowsFromBootProvider::class, 'ThrowingPlugin');
        $loader->load(HealthyProvider::class, 'HealthyPlugin');
        $loader->load(SecondHealthyProvider::class, 'SecondPlugin');
        $loader->bootPending();

        $this->assertTrue(HealthyProvider::$booted, 'A healthy plugin after a broken one must still boot.');
        $this->assertTrue(SecondHealthyProvider::$booted);
        $this->assertSame(['ThrowingPlugin'], array_keys($this->failures()));
    }

    /**
     * Laravel keys registered providers by class, so a shared wrapper class
     * would collapse every plugin into one. This is the regression that
     * guards against going back to that shape.
     */
    public function test_every_provider_is_kept_not_just_the_first(): void
    {
        $loader = $this->loader();

        $loader->load(HealthyProvider::class, 'HealthyPlugin');
        $loader->load(SecondHealthyProvider::class, 'SecondPlugin');
        $loader->bootPending();

        $this->assertTrue(HealthyProvider::$registered);
        $this->assertTrue(SecondHealthyProvider::$registered);
        $this->assertTrue(HealthyProvider::$booted);
        $this->assertTrue(SecondHealthyProvider::$booted);
    }

    /**
     * Core offers the same provider from the manifest list and from the
     * database list. The second offer must not register it twice.
     */
    public function test_the_same_provider_is_not_registered_twice(): void
    {
        $loader = $this->loader();

        $loader->load(CountingProvider::class, 'CountingPlugin');
        $loader->load(CountingProvider::class, 'CountingPlugin');
        $loader->bootPending();

        $this->assertSame(1, CountingProvider::$registerCalls);
        $this->assertSame(1, CountingProvider::$bootCalls);
    }

    /**
     * The earlier of those two paths runs before some core bindings exist, so
     * a provider can fail there and register fine when the later one reaches
     * it. Marking it as seen on failure would turn a recoverable ordering
     * problem into a plugin that never loads.
     */
    public function test_a_failed_registration_is_retried_and_clears_the_failure(): void
    {
        $loader = $this->loader();

        FlakyProvider::$failNext = true;
        $loader->load(FlakyProvider::class, 'FlakyPlugin');
        $this->assertSame('register', $this->failures()['FlakyPlugin']['phase'] ?? null);

        FlakyProvider::$failNext = false;
        $loader->load(FlakyProvider::class, 'FlakyPlugin');
        $loader->bootPending();

        $this->assertTrue(FlakyProvider::$booted, 'The retry is expected to register and boot it.');
        $this->assertSame([], $this->failures(), 'A plugin that recovered must not stay reported as broken.');
    }

    /**
     * Bindings and singletons declared as properties are applied by Laravel's
     * Application::register(). These providers do not go through it.
     */
    public function test_declared_bindings_and_singletons_are_applied(): void
    {
        $loader = $this->loader();

        $loader->load(DeclaresBindingsProvider::class, 'BindingPlugin');

        $this->assertTrue($this->app->bound('guarded.binding'));
        $this->assertTrue($this->app->bound('guarded.singleton'));
    }

    /**
     * booting()/booted() callbacks a provider registers are stored on that
     * provider, and Laravel fires them around the provider it boots. Nothing
     * boots these providers but the loader, so it fires them.
     */
    public function test_booting_and_booted_callbacks_still_fire(): void
    {
        $loader = $this->loader();

        $loader->load(RegistersBootCallbacksProvider::class, 'CallbackPlugin');
        $loader->bootPending();

        $this->assertSame(['booting', 'boot', 'booted'], RegistersBootCallbacksProvider::$order);
    }

    public function test_the_failure_is_logged_for_an_operator(): void
    {
        Log::spy();

        $loader = $this->loader();
        $loader->load(ThrowsFromBootProvider::class, 'ThrowingPlugin');
        $loader->bootPending();

        Log::shouldHaveReceived('error')->once();
    }

    protected function setUp(): void
    {
        parent::setUp();

        HealthyProvider::$registered = HealthyProvider::$booted = false;
        SecondHealthyProvider::$registered = SecondHealthyProvider::$booted = false;
        CountingProvider::$registerCalls = CountingProvider::$bootCalls = 0;
        FlakyProvider::$failNext = false;
        FlakyProvider::$booted = false;
        RegistersBootCallbacksProvider::$order = [];
    }
}

class HealthyProvider extends ServiceProvider
{
    public static bool $registered = false;

    public static bool $booted = false;

    public function register(): void
    {
        static::$registered = true;
    }

    public function boot(): void
    {
        static::$booted = true;
    }
}

class SecondHealthyProvider extends ServiceProvider
{
    public static bool $registered = false;

    public static bool $booted = false;

    public function register(): void
    {
        static::$registered = true;
    }

    public function boot(): void
    {
        static::$booted = true;
    }
}

class CountingProvider extends ServiceProvider
{
    public static int $registerCalls = 0;

    public static int $bootCalls = 0;

    public function register(): void
    {
        static::$registerCalls++;
    }

    public function boot(): void
    {
        static::$bootCalls++;
    }
}

class FlakyProvider extends ServiceProvider
{
    public static bool $failNext = false;

    public static bool $booted = false;

    public function register(): void
    {
        if (static::$failNext) {
            throw new \RuntimeException('not ready yet');
        }
    }

    public function boot(): void
    {
        static::$booted = true;
    }
}

class ThrowsFromBootProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        throw new \RuntimeException('boot blew up');
    }
}

class ThrowsErrorFromBootProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->app->make('App\Services\Content\NoSuchClassAtAll');
    }
}

class ThrowsFromRegisterProvider extends ServiceProvider
{
    public function register(): void
    {
        throw new \RuntimeException('register blew up');
    }
}

class DeclaresBindingsProvider extends ServiceProvider
{
    /** @var array<string, mixed> */
    public array $bindings = ['guarded.binding' => HealthyProvider::class];

    /** @var array<array-key, mixed> */
    public array $singletons = ['guarded.singleton' => HealthyProvider::class];

    public function register(): void {}
}

class RegistersBootCallbacksProvider extends ServiceProvider
{
    /** @var list<string> */
    public static array $order = [];

    public function register(): void
    {
        $this->booting(static function (): void {
            static::$order[] = 'booting';
        });
        $this->booted(static function (): void {
            static::$order[] = 'booted';
        });
    }

    public function boot(): void
    {
        static::$order[] = 'boot';
    }
}
