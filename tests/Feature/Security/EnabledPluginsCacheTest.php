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

namespace Tests\Feature\Security;

use App\Providers\PluginServiceProvider;
use App\Services\Plugin\EnabledPluginsCache;
use App\Services\Security\IntegrityKeyring;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * The enabled-plugins cache read in register() (#803).
 *
 * It decides which plugins' manifest providers register before the database
 * is reachable. It is JSON, never executed, and on a keyed site a file that
 * does not carry a valid MAC counts as no cache: nothing registers.
 *
 * Storage is redirected to `storage/framework/testing/`.
 */
class EnabledPluginsCacheTest extends TestCase
{
    private string $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = storage_path('framework/testing/plugin-cache-'.uniqid());
        File::makeDirectory($this->storage.'/framework/cache', 0755, true);
        $this->app->useStoragePath($this->storage);
        config(['security.integrity_keys.current' => '', 'security.integrity_keys.previous' => '']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->storage);

        parent::tearDown();
    }

    public function test_a_keyed_cache_round_trips(): void
    {
        $this->useKey();
        EnabledPluginsCache::make()->write(['DixlaseBlog', 'DixlaseSEO']);

        $this->assertSame(['DixlaseBlog', 'DixlaseSEO'], EnabledPluginsCache::make()->read());
        $this->assertSame(2, $this->stored()['mac']['key_version']);
    }

    public function test_a_plugin_added_to_the_file_registers_nothing_and_is_logged(): void
    {
        $this->useKey();
        EnabledPluginsCache::make()->write(['DixlaseBlog']);

        $data = $this->stored();
        $data['plugins'][] = 'Disabled';
        $this->store($data);

        Log::spy();
        $this->assertSame([], EnabledPluginsCache::make()->read());
        Log::shouldHaveReceived('warning')->withArgs(
            fn (string $message) => str_contains($message, 'does not match its MAC'),
        )->once();
    }

    public function test_a_removed_mac_registers_nothing(): void
    {
        $this->useKey();
        $this->store(['plugins' => ['DixlaseBlog']]);

        $this->assertSame([], EnabledPluginsCache::make()->read());
    }

    public function test_a_site_without_a_key_reads_the_plain_list(): void
    {
        EnabledPluginsCache::make()->write(['DixlaseBlog']);

        $this->assertArrayNotHasKey('mac', $this->stored());
        $this->assertSame(['DixlaseBlog'], EnabledPluginsCache::make()->read());
    }

    public function test_a_cache_from_before_a_rotation_is_stale_not_trusted(): void
    {
        $old = IntegrityKeyring::generate(2);
        config(['security.integrity_keys.current' => $old]);
        EnabledPluginsCache::make()->write(['DixlaseBlog']);

        config([
            'security.integrity_keys.current' => IntegrityKeyring::generate(3),
            'security.integrity_keys.previous' => $old,
        ]);

        $this->assertSame([], EnabledPluginsCache::make()->read());

        EnabledPluginsCache::make()->write(['DixlaseBlog']);
        $this->assertSame(['DixlaseBlog'], EnabledPluginsCache::make()->read());
    }

    public function test_malformed_content_registers_nothing(): void
    {
        $this->useKey();
        File::put($this->path(), '<?php return ["DixlaseBlog"];');
        $this->assertSame([], EnabledPluginsCache::make()->read());

        $this->store(['plugins' => [['nested']]]);
        $this->assertSame([], EnabledPluginsCache::make()->read());
    }

    public function test_the_old_php_cache_is_never_executed_and_is_removed(): void
    {
        $legacy = $this->storage.'/framework/cache/'.EnabledPluginsCache::LEGACY_FILENAME;
        File::put($legacy, '<?php throw new \RuntimeException("executed");');

        $this->assertSame([], EnabledPluginsCache::make()->read());

        EnabledPluginsCache::make()->write(['DixlaseBlog']);
        $this->assertFileDoesNotExist($legacy);
    }

    public function test_an_unchanged_list_is_not_rewritten(): void
    {
        $this->useKey();
        EnabledPluginsCache::make()->write(['DixlaseBlog']);
        touch($this->path(), 1_000_000_000);
        clearstatcache();

        EnabledPluginsCache::make()->write(['DixlaseBlog']);
        clearstatcache();
        $this->assertSame(1_000_000_000, filemtime($this->path()));

        EnabledPluginsCache::make()->write(['DixlaseBlog', 'DixlaseSEO']);
        clearstatcache();
        $this->assertNotSame(1_000_000_000, filemtime($this->path()));
        $this->assertSame([], glob($this->path().'.*.tmp'), 'no temporary file is left behind');
    }

    public function test_clearing_removes_the_cache(): void
    {
        EnabledPluginsCache::make()->write(['DixlaseBlog']);

        PluginServiceProvider::clearEnabledPluginsCache();

        $this->assertFileDoesNotExist($this->path());
    }

    public function test_the_provider_reads_no_plugins_from_a_tampered_cache(): void
    {
        $this->useKey();
        EnabledPluginsCache::make()->write(['DixlaseBlog']);
        $data = $this->stored();
        $data['plugins'] = ['Other'];
        $this->store($data);

        $read = (function (): array {
            return $this->getEnabledPluginsFromCache();
        })->call(new PluginServiceProvider($this->app));

        $this->assertSame([], $read);
    }

    private function useKey(): void
    {
        config(['security.integrity_keys.current' => IntegrityKeyring::generate(2)]);
    }

    private function path(): string
    {
        return $this->storage.'/framework/cache/'.EnabledPluginsCache::FILENAME;
    }

    /**
     * @return array<string, mixed>
     */
    private function stored(): array
    {
        return json_decode(File::get($this->path()), true);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function store(array $data): void
    {
        File::put($this->path(), json_encode($data));
    }
}
