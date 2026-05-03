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

namespace App\Repositories;

use App\Contracts\Repositories\SiteSettingRepositoryInterface;
use App\Contracts\Site\SiteContextInterface;
use App\Enums\SettingScope;
use App\Models\GlobalSetting;
use App\Models\SiteSetting;
use App\Services\Site\Exceptions\UnknownSettingException;
use App\Services\Site\SettingDefinitionRegistry;
use App\Services\Site\SettingResolver;
use Illuminate\Database\Eloquent\Model;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * Settings repository that delegates to SettingResolver so reads/writes
 * follow the per-key Global / PerSite / Overridable scope. Preserves the
 * legacy SiteSetting::getValue() / setValue() callsites by wrapping the
 * resolver with the older Repository signature.
 */
class SiteSettingRepository extends AbstractSettingRepository implements SiteSettingRepositoryInterface
{
    public function __construct(
        private readonly SettingResolver $resolver,
        private readonly SettingDefinitionRegistry $registry,
        private readonly SiteContextInterface $siteContext,
    ) {
        $this->cachePrefix = 'site_setting:';
        $this->cacheAllKey = 'site_settings_all';
        $this->cacheTtl = 10;
    }

    /**
     * {@inheritDoc}
     */
    protected function getModelClass(): string
    {
        return SiteSetting::class;
    }

    /**
     * Resolve a setting via SettingResolver. Falls through to the caller's
     * explicit default when the resolved value is null.
     *
     * Strict mode: unregistered keys raise UnknownSettingException so
     * typos are caught at the call site.
     *
     * {@inheritDoc}
     */
    public function get(string $name, mixed $default = null): mixed
    {
        $value = $this->resolver->get($name);

        return $value ?? $default;
    }

    /**
     * Persist via SettingResolver. The model returned is whichever store
     * the resolver actually wrote to (GlobalSetting or SiteSetting),
     * preserving the Repository::set() contract for legacy callers.
     *
     * {@inheritDoc}
     */
    public function set(string $name, mixed $value): Model
    {
        $this->resolver->set($name, $value);
        $this->clearCache($name);

        return $this->lookupModel($name);
    }

    /**
     * Return all known setting keys with their resolved values.
     *
     * Iterates over the SettingDefinitionRegistry rather than dumping a
     * single table, so the result reflects scope-aware resolution
     * (per-site values override globals on Overridable keys).
     *
     * {@inheritDoc}
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $result = [];
        foreach ($this->registry->all() as $name => $_definition) {
            $result[$name] = $this->resolver->get($name);
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $name): bool
    {
        try {
            return $this->resolver->get($name) !== null;
        } catch (UnknownSettingException) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $name): bool
    {
        try {
            $this->resolver->delete($name);
            $this->clearCache($name);

            return true;
        } catch (UnknownSettingException) {
            return false;
        }
    }

    /**
     * Returns the SiteSetting with eager-loaded media relation. Restricted
     * to the current site so callers see their own row.
     */
    public function findWithRelations(string $name): ?SiteSetting
    {
        return SiteSetting::with('defaultOgpImage')
            ->where('name', $name)
            ->first();
    }

    /**
     * Locate the row that the resolver just wrote to so set() can return
     * a concrete Model. Returns a non-persisted placeholder if no row is
     * found (e.g. the key is unregistered, which would have thrown
     * earlier anyway, but we keep the type contract).
     */
    private function lookupModel(string $name): Model
    {
        $definition = $this->registry->get($name);
        if ($definition === null) {
            return new SiteSetting(['name' => $name]);
        }

        if ($definition->scope === SettingScope::Global) {
            return GlobalSetting::query()->where('name', $name)->first()
                ?? new GlobalSetting(['name' => $name]);
        }

        // Overridable without explicit site_id was written globally; with
        // a site context, the per-site row is the canonical answer for
        // legacy callers that use the result.
        if ($definition->scope === SettingScope::Overridable) {
            $perSite = SiteSetting::query()
                ->withoutGlobalScope('belongs_to_site')
                ->where('site_id', $this->siteContext->currentSiteId())
                ->where('name', $name)
                ->first();
            if ($perSite !== null) {
                return $perSite;
            }

            return GlobalSetting::query()->where('name', $name)->first()
                ?? new GlobalSetting(['name' => $name]);
        }

        return SiteSetting::query()
            ->withoutGlobalScope('belongs_to_site')
            ->where('site_id', $this->siteContext->currentSiteId())
            ->where('name', $name)
            ->first()
            ?? new SiteSetting(['name' => $name, 'site_id' => $this->siteContext->currentSiteId()]);
    }
}
