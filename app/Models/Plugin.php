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

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

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
}
