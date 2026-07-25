<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc. and Dixlase contributors
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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Plugin metadata model
 */
class Plugin extends Model
{
    /**
     * Boot hook: keep site_plugin_activations in sync with the legacy
     * enabled_at flag for v0.1.0. When the activation table is missing
     * (during installation) or the primary site has not been seeded yet,
     * the sync is silently skipped. v2 admin UIs will manage activation
     * rows directly per-site.
     */
    protected static function booted(): void
    {
        static::saved(function (Plugin $plugin): void {
            if (! $plugin->wasChanged('enabled_at')) {
                return;
            }
            self::syncPrimarySiteActivation($plugin);
        });
    }

    private static function syncPrimarySiteActivation(Plugin $plugin): void
    {
        if (! Schema::hasTable('site_plugin_activations')) {
            return;
        }
        if (! Schema::hasTable('sites')) {
            return;
        }

        $primarySite = Site::primary();
        if ($primarySite === null) {
            return;
        }

        SitePluginActivation::query()->updateOrCreate(
            ['site_id' => $primarySite->id, 'plugin_id' => $plugin->id],
            [
                'is_active' => $plugin->enabled_at !== null,
                'activated_at' => $plugin->enabled_at,
            ]
        );
    }

    /**
     * Mass assignable fields
     */
    protected $fillable = [
        'name',
        'package_name',
        'directory',
        'namespace',
        'slug',
        'version',
        'author',
        'email',
        'url',
        'license',
        'description',
        'installed_at',
        'enabled_at',
        'source_id',
        'source_repo',
        'available_version',
        'last_notified_version',
        'available_version_published_at',
        'release_url',
        'release_notes',
        'last_version_check',
        'update_failed_at',
        'update_failure_reason',
        'signing_key_id',
        'author_id',
        'authority_key_id',
        'installed_from_url',
        'installation_method',
    ];

    /**
     * Cast settings
     */
    protected $casts = [
        'installed_at' => 'datetime',
        'enabled_at' => 'datetime',
        'last_version_check' => 'datetime',
        'available_version_published_at' => 'datetime',
        'update_failed_at' => 'datetime',
    ];

    /**
     * Extension source that this plugin was installed from
     */
    public function source(): BelongsTo
    {
        return $this->belongsTo(ExtensionSource::class, 'source_id');
    }

    /**
     * Check if an update is available from the source
     */
    public function hasUpdateAvailable(): bool
    {
        return $this->available_version !== null
            && version_compare($this->available_version, $this->version, '>');
    }

    /**
     * Scope to retrieve enabled plugins
     */
    public function scopeEnabled($query)
    {
        return $query->whereNotNull('enabled_at');
    }

    /**
     * Scope for installed plugins
     */
    public function scopeInstalled($query)
    {
        return $query->whereNotNull('installed_at');
    }

    /**
     * Check if plugin is installed
     */
    public function isInstalled(): bool
    {
        return ! is_null($this->installed_at);
    }

    /**
     * Check if plugin is enabled
     */
    public function isEnabled(): bool
    {
        return ! is_null($this->enabled_at);
    }

    /**
     * Kept for backward compatibility (deprecated)
     *
     * @deprecated Use isEnabled() instead
     */
    public function isActivated(): bool
    {
        return $this->isEnabled();
    }

    /**
     * Resolve the on-disk plugin directory name from a slug.
     *
     * Many call sites historically reconstructed the directory by piping
     * the slug through `Str::studly(str_replace('-', '_', $slug))`. That
     * conversion silently mangles plugins whose canonical name contains
     * an uppercase acronym — e.g. the slug `dixlase-seo` becomes
     * `DixlaseSeo`, which on a case-sensitive filesystem does not match
     * the actual `DixlaseSEO` directory. The result is either a missing
     * directory (no plugin found at all) or, on case-insensitive
     * filesystems, a wrong-case path that PHP iterators
     * (`RecursiveDirectoryIterator::__construct`) refuse to open.
     *
     * The lookup runs three lanes in order:
     *
     *   1. **DB**: look up the row by slug and use the stored
     *      `directory` column as the source of truth. The on-disk index
     *      is consulted so the returned name always matches the actual
     *      filesystem casing.
     *   2. **Studly heuristic**: for plugins whose name has no
     *      acronyms, `Str::studly` still produces the right answer.
     *      Used as a no-DB fast path (early boot, tests, etc.).
     *   3. **Normalized fallback**: strip dashes and lowercase both
     *      sides; matches `dixlase-seo` → `DixlaseSEO` without needing
     *      the DB.
     *
     * Returns `null` when no on-disk directory matches.
     */
    public static function resolveDirectoryFromSlug(string $slug): ?string
    {
        if ($slug === '') {
            return null;
        }

        $base = base_path('plugins');
        $actualDirs = [];
        foreach (glob("{$base}/*", GLOB_ONLYDIR) ?: [] as $dir) {
            $name = basename($dir);
            $actualDirs[strtolower($name)] = $name;
        }

        if (empty($actualDirs)) {
            return null;
        }

        try {
            $stored = self::where('slug', $slug)->value('directory');
            if (is_string($stored) && $stored !== '') {
                $key = strtolower($stored);
                if (isset($actualDirs[$key])) {
                    return $actualDirs[$key];
                }
            }
        } catch (\Throwable $e) {
            // DB may be unavailable during early boot or in unit tests.
        }

        $studly = Str::studly(str_replace('-', '_', $slug));
        if (isset($actualDirs[strtolower($studly)])) {
            return $actualDirs[strtolower($studly)];
        }

        $normalized = strtolower(str_replace('-', '', $slug));
        if (isset($actualDirs[$normalized])) {
            return $actualDirs[$normalized];
        }

        return null;
    }
}
