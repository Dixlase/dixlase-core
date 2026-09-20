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

namespace App\Services\Extension;

use App\Contracts\Extension\ProvidesSettingsDefaultsInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill an extension's declared settings defaults into its DB
 * table on install and update, using insertOrIgnore so
 * operator-edited values are never overwritten.
 *
 * See {@see ProvidesSettingsDefaultsInterface} for the "why" — this
 * class is the runtime side. The install / update commands call
 * {@see self::syncForExtension()} at the same post-swap phase where
 * view:clear and the audit rescan already fire.
 */
class ExtensionSettingsDefaultsSync
{
    /**
     * Walk the extension's manifest, find any service provider that
     * implements {@see ProvidesSettingsDefaultsInterface}, and
     * insertOrIgnore its defaults into its declared table.
     *
     * Every non-throwing branch is a no-op path: missing manifest,
     * unreadable manifest, no `providers` entry, no provider that
     * implements the contract, missing DB table (extension has not
     * migrated yet), no defaults declared, or `insertOrIgnore`
     * finding every row already present — the caller sees a plain
     * return in every case. Exceptions are caught and logged rather
     * than surfaced because this is a post-swap best-effort step: a
     * failure here must not fail an update whose file and migration
     * halves already committed.
     *
     * @param  string  $extensionDir  Absolute path to the extension's
     *                                directory on disk (i.e. the folder
     *                                that holds the manifest file).
     * @param  'plugin'|'theme'  $kind  Whether the manifest is
     *                                  `plugin.json` or `theme.json`.
     * @return array{synced_keys: list<string>, table: string|null}
     *                                                              Diagnostic outcome — the caller can log what
     *                                                              got inserted. Empty synced_keys means "nothing
     *                                                              to do" (contract satisfied, or contract absent).
     */
    public function syncForExtension(string $extensionDir, string $kind): array
    {
        $blank = ['synced_keys' => [], 'table' => null];

        $manifestFile = $kind === 'theme' ? 'theme.json' : 'plugin.json';
        $manifestPath = rtrim($extensionDir, '/').'/'.$manifestFile;

        // dixlase.json is an accepted alias for plugin.json — same
        // fallback the runtime PluginServiceProvider uses. Themes
        // do not have a dixlase.json alias.
        if ($kind === 'plugin' && ! File::exists($manifestPath)) {
            $aliasPath = rtrim($extensionDir, '/').'/dixlase.json';
            if (File::exists($aliasPath)) {
                $manifestPath = $aliasPath;
            }
        }

        if (! File::exists($manifestPath)) {
            return $blank;
        }

        try {
            $raw = File::get($manifestPath);
        } catch (\Throwable $e) {
            Log::warning('ExtensionSettingsDefaultsSync: could not read manifest', [
                'manifest' => $manifestPath,
                'error' => $e->getMessage(),
            ]);

            return $blank;
        }

        $manifest = json_decode($raw, true);
        if (! is_array($manifest) || ! isset($manifest['providers']) || ! is_array($manifest['providers'])) {
            return $blank;
        }

        foreach ($manifest['providers'] as $providerClass) {
            if (! is_string($providerClass) || $providerClass === '' || ! class_exists($providerClass)) {
                continue;
            }

            if (! is_subclass_of($providerClass, ProvidesSettingsDefaultsInterface::class)
                && ! in_array(ProvidesSettingsDefaultsInterface::class, class_implements($providerClass) ?: [], true)) {
                continue;
            }

            try {
                /** @var ProvidesSettingsDefaultsInterface $provider */
                $provider = app()->make($providerClass);
            } catch (\Throwable $e) {
                Log::warning('ExtensionSettingsDefaultsSync: provider instantiation failed', [
                    'provider' => $providerClass,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            $table = $provider->getSettingsTable();
            if (! is_string($table) || $table === '' || ! Schema::hasTable($table)) {
                // Table absent → the extension migrations have not been
                // applied yet, or this provider's kind does not run
                // in this environment. Either way, nothing to sync.
                continue;
            }

            $defaults = $provider->getSettingsDefaults();
            if (! is_array($defaults) || $defaults === []) {
                return ['synced_keys' => [], 'table' => $table];
            }

            return $this->insertMissing($table, $defaults);
        }

        return $blank;
    }

    /**
     * Insert one row per key that is not already present in the
     * table. Uses insertOrIgnore keyed by the `name` column so
     * concurrent installs / updates against the same extension
     * (unusual but possible in HA setups) cannot double-insert.
     *
     * @param  array<string, string|int|float|bool|null>  $defaults
     * @return array{synced_keys: list<string>, table: string}
     */
    protected function insertMissing(string $table, array $defaults): array
    {
        $existing = DB::table($table)
            ->whereIn('name', array_keys($defaults))
            ->pluck('name')
            ->all();
        $existing = array_flip($existing);

        $now = now();
        $synced = [];
        $rows = [];

        foreach ($defaults as $name => $value) {
            if (! is_string($name) || $name === '') {
                continue;
            }
            if (isset($existing[$name])) {
                continue;
            }

            $rows[] = [
                'name' => $name,
                'value' => $this->normaliseValue($value),
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $synced[] = $name;
        }

        if ($rows !== []) {
            // insertOrIgnore covers the race where two concurrent
            // callers both compute `$existing` before either has
            // inserted; the second one's insert of the same `name`
            // silently no-ops on the unique constraint.
            DB::table($table)->insertOrIgnore($rows);
        }

        return ['synced_keys' => $synced, 'table' => $table];
    }

    /**
     * Coerce contract-declared value types into what the settings
     * table's `value` column (text, nullable) accepts.
     */
    protected function normaliseValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_string($value)) {
            return $value;
        }

        // Array / object shouldn't reach here (the contract's
        // return type excludes them), but coerce defensively so a
        // bad contract implementation logs a JSON blob rather
        // than crashing the whole install.
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null;
    }
}
