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

use App\Services\Security\IntegrityKeyring;
use App\Services\Security\IntegritySeal;
use Illuminate\Support\Facades\Log;

/**
 * @internal Core only. Do not reference from plugins/themes
 *
 * The list of enabled plugin directories that PluginServiceProvider reads in
 * register(), before the database can be asked (dixlase-core#803).
 *
 * It used to be a PHP file loaded with `require`, so anything that could
 * write to `storage/framework/cache/` could run code at the next boot, or
 * change which plugins' providers registered. It is now plain JSON, and when
 * an integrity key is configured it carries a MAC made with the current
 * key. A file that does not verify is treated as no cache: no plugin
 * providers register in that request, the mismatch is logged, and boot()
 * writes a fresh one from the database. Failing closed is safe here, because
 * a plugin that did not register is visible and the next request is whole.
 *
 * Only the current key is accepted. After a rotation the first request
 * treats the old file as stale and boot() rewrites it.
 */
class EnabledPluginsCache
{
    public const FILENAME = 'enabled_plugins.json';

    /** The PHP cache written before #803; removed whenever the cache is written or cleared */
    public const LEGACY_FILENAME = 'enabled_plugins.php';

    public function __construct(private IntegritySeal $seal) {}

    public static function make(): self
    {
        return new self(new IntegritySeal(new IntegrityKeyring()));
    }

    public function path(): string
    {
        return storage_path('framework/cache/'.self::FILENAME);
    }

    /**
     * The enabled plugin directories, or an empty list when there is no
     * cache or it cannot be trusted.
     *
     * @return list<string>
     */
    public function read(): array
    {
        $path = $this->path();
        if (! is_file($path)) {
            return [];
        }

        $data = json_decode((string) @file_get_contents($path), true);
        $plugins = is_array($data) ? ($data['plugins'] ?? null) : null;

        if (! is_array($plugins) || array_filter($plugins, fn ($p) => ! is_string($p)) !== []) {
            Log::warning('Enabled-plugins cache is malformed; no plugin providers registered until it is rebuilt', [
                'path' => $path,
            ]);

            return [];
        }

        $plugins = array_values($plugins);
        $keyring = $this->seal->keyring();

        if (! $keyring->isConfigured()) {
            return $plugins;
        }

        $mac = $data['mac'] ?? null;
        if (is_array($mac) && ($mac['key_version'] ?? null) !== $keyring->currentVersion()
            && is_int($mac['key_version'] ?? null) && $keyring->has($mac['key_version'])) {
            // Written before a key rotation; not tampering, just stale.
            return [];
        }

        $status = $this->seal->check(IntegrityKeyring::PURPOSE_PLUGIN_CACHE, $this->message($plugins), $mac);
        if ($status !== IntegritySeal::VALID) {
            Log::warning('Enabled-plugins cache does not match its MAC; no plugin providers registered until it is rebuilt', [
                'path' => $path,
                'seal' => $status,
            ]);

            return [];
        }

        return $plugins;
    }

    /**
     * Write the list, unless the file on disk already says exactly this.
     *
     * The file is replaced in one step (write, then rename), so a request
     * reading it never sees half of it.
     *
     * @param  iterable<string>  $plugins
     */
    public function write(iterable $plugins): void
    {
        $list = [];
        foreach ($plugins as $plugin) {
            $list[] = (string) $plugin;
        }

        $data = ['plugins' => $list];
        $mac = $this->seal->sign(IntegrityKeyring::PURPOSE_PLUGIN_CACHE, $this->message($list));
        if ($mac !== null) {
            $data['mac'] = $mac;
        }
        $content = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";

        $path = $this->path();
        $this->removeLegacy();

        if (is_file($path) && @file_get_contents($path) === $content) {
            return;
        }

        $dir = dirname($path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $tmp = $path.'.'.bin2hex(random_bytes(6)).'.tmp';
        if (@file_put_contents($tmp, $content) === false) {
            return;
        }
        if (! @rename($tmp, $path)) {
            @unlink($tmp);
        }
    }

    public function clear(): void
    {
        if (is_file($this->path())) {
            @unlink($this->path());
        }
        $this->removeLegacy();
    }

    /**
     * @param  list<string>  $plugins
     */
    private function message(array $plugins): string
    {
        return 'dixlase-enabled-plugins|'.json_encode($plugins, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function removeLegacy(): void
    {
        $legacy = storage_path('framework/cache/'.self::LEGACY_FILENAME);
        if (is_file($legacy)) {
            @unlink($legacy);
        }
    }
}
