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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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

namespace App\Models\Traits;

use App\Contracts\Site\SiteContextInterface;
use App\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Throwable;

/**
 * Scopes a model to the current site automatically.
 *
 * Applies a global scope that filters queries by the current site_id from
 * SiteContext, and assigns site_id on creation when not explicitly set.
 *
 * Models using this trait must:
 *   - have a site_id column (foreign key to sites.id)
 *   - include 'site_id' in $fillable (or use guarded = [])
 *
 * To bypass the scope intentionally:
 *   - $query->allSites()             cross-site query
 *   - $query->forSite($siteId)       query a specific site
 *   - Model::withoutGlobalScope('belongs_to_site')->...
 *
 * To create a row without auto-injecting site_id (e.g. network-level rows
 * with site_id = NULL such as CLI-issued network API keys):
 *   - Model::withoutSiteContext(fn () => Model::create(['site_id' => null, ...]))
 */
trait BelongsToSite
{
    /**
     * Re-entrant counter of nested withoutSiteContext() blocks. While > 0
     * the creating hook does not auto-inject site_id, so callers can write
     * rows whose site_id is explicitly null (network-level rows).
     */
    private static int $belongsToSiteSuspendDepth = 0;

    public static function bootBelongsToSite(): void
    {
        static::addGlobalScope('belongs_to_site', function (Builder $query) {
            try {
                $siteId = app(SiteContextInterface::class)->currentSiteId();
                $query->where($query->getModel()->getTable().'.site_id', $siteId);
            } catch (Throwable) {
                // SiteContext cannot be resolved (e.g. during installation
                // or when the sites table is not yet seeded). Skip scoping
                // so the query still works against raw data.
            }
        });

        static::creating(function (Model $model) {
            if (static::$belongsToSiteSuspendDepth > 0) {
                // Caller is inside withoutSiteContext(); honor whatever
                // site_id (including null) was passed explicitly.
                return;
            }

            if (empty($model->getAttribute('site_id'))) {
                try {
                    $model->setAttribute(
                        'site_id',
                        app(SiteContextInterface::class)->currentSiteId()
                    );
                } catch (Throwable) {
                    // Caller is responsible for providing site_id when
                    // SiteContext is unavailable (CLI seeders, tests, etc.).
                }
            }
        });
    }

    /**
     * Run a callback with site context auto-injection disabled. Inside the
     * callback, model creation does not auto-fill site_id, so callers can
     * write rows with site_id = null (network-level rows). Re-entrant.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function withoutSiteContext(callable $callback): mixed
    {
        static::$belongsToSiteSuspendDepth++;
        try {
            return $callback();
        } finally {
            static::$belongsToSiteSuspendDepth--;
        }
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Bypass the site scope entirely. Use for cross-site queries
     * (e.g. network admin reports).
     */
    public function scopeAllSites(Builder $query): Builder
    {
        return $query->withoutGlobalScope('belongs_to_site');
    }

    /**
     * Query rows owned by a specific site (bypasses the implicit
     * current-site filter).
     */
    public function scopeForSite(Builder $query, int $siteId): Builder
    {
        return $query->withoutGlobalScope('belongs_to_site')
            ->where($this->getTable().'.site_id', $siteId);
    }
}
