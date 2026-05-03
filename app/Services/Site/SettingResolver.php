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

declare(strict_types=1);

namespace App\Services\Site;

use App\Contracts\Site\SiteContextInterface;
use App\Enums\SettingScope;
use App\Models\GlobalSetting;
use App\Models\SiteSetting;
use App\Services\Site\Exceptions\UnknownSettingException;
use InvalidArgumentException;

/**
 * Resolves setting values according to the 3-mode scope model.
 *
 * - Global       reads/writes go to global_settings only
 * - PerSite      reads/writes go to site_settings (filtered by site_id)
 * - Overridable  read order: site_settings -> global_settings -> default
 *                set() with explicit site_id writes to site_settings,
 *                set() without site_id writes to global_settings
 *
 * All keys must be registered in SettingDefinitionRegistry; unregistered
 * keys raise UnknownSettingException.
 */
class SettingResolver
{
    public function __construct(
        private readonly SettingDefinitionRegistry $registry,
        private readonly SiteContextInterface $siteContext,
    ) {}

    /**
     * Resolve the value for a setting key. Falls back through site ->
     * global -> default according to the key's scope.
     *
     * When $siteId is null and the key accepts per-site values, the
     * current site is used.
     */
    public function get(string $key, ?int $siteId = null): mixed
    {
        $definition = $this->requireDefinition($key);

        if ($definition->acceptsPerSite()) {
            $siteId ??= $this->siteContext->currentSiteId();
            $row = SiteSetting::query()
                ->withoutGlobalScope('belongs_to_site')
                ->where('site_id', $siteId)
                ->where('name', $key)
                ->first();
            if ($row !== null) {
                return $this->castValue($row->value, $definition->type);
            }
        }

        if ($definition->acceptsGlobal()) {
            $row = GlobalSetting::query()->where('name', $key)->first();
            if ($row !== null) {
                return $this->castValue($row->value, $definition->type);
            }
        }

        return $definition->default;
    }

    /**
     * Set a value for a setting key.
     *
     * Routing rules:
     *   - Global keys always write to global_settings
     *   - PerSite keys always write to site_settings (current or given site)
     *   - Overridable keys with explicit $siteId write to site_settings,
     *     otherwise to global_settings
     */
    public function set(string $key, mixed $value, ?int $siteId = null): void
    {
        $definition = $this->requireDefinition($key);

        match ($definition->scope) {
            SettingScope::Global => $this->writeGlobal($key, $value),
            SettingScope::PerSite => $this->writePerSite(
                $key,
                $value,
                $siteId ?? $this->siteContext->currentSiteId(),
            ),
            SettingScope::Overridable => $siteId !== null
                ? $this->writePerSite($key, $value, $siteId)
                : $this->writeGlobal($key, $value),
        };
    }

    /**
     * Force a write to the network-wide store. Rejects PerSite-only keys.
     */
    public function setGlobal(string $key, mixed $value): void
    {
        $definition = $this->requireDefinition($key);
        if (! $definition->acceptsGlobal()) {
            throw new InvalidArgumentException(
                "Setting '{$key}' is per-site only and cannot be set globally."
            );
        }
        $this->writeGlobal($key, $value);
    }

    /**
     * Force a write to the per-site store. Rejects Global-only keys.
     */
    public function setForSite(string $key, mixed $value, int $siteId): void
    {
        $definition = $this->requireDefinition($key);
        if (! $definition->acceptsPerSite()) {
            throw new InvalidArgumentException(
                "Setting '{$key}' is global only and cannot be set per-site."
            );
        }
        $this->writePerSite($key, $value, $siteId);
    }

    /**
     * Delete a stored value. For Overridable keys, $siteId chooses which
     * tier (per-site or global) to clear. For Global / PerSite keys the
     * relevant tier is deleted regardless of $siteId.
     */
    public function delete(string $key, ?int $siteId = null): void
    {
        $definition = $this->requireDefinition($key);

        if ($definition->scope === SettingScope::Global) {
            GlobalSetting::query()->where('name', $key)->delete();

            return;
        }

        if ($definition->scope === SettingScope::PerSite) {
            $siteId ??= $this->siteContext->currentSiteId();
            SiteSetting::query()
                ->withoutGlobalScope('belongs_to_site')
                ->where('site_id', $siteId)
                ->where('name', $key)
                ->delete();

            return;
        }

        // Overridable: $siteId chooses tier
        if ($siteId !== null) {
            SiteSetting::query()
                ->withoutGlobalScope('belongs_to_site')
                ->where('site_id', $siteId)
                ->where('name', $key)
                ->delete();
        } else {
            GlobalSetting::query()->where('name', $key)->delete();
        }
    }

    private function requireDefinition(string $key): SettingDefinition
    {
        $definition = $this->registry->get($key);
        if ($definition === null) {
            throw new UnknownSettingException($key);
        }

        return $definition;
    }

    private function writeGlobal(string $key, mixed $value): void
    {
        GlobalSetting::query()->updateOrCreate(
            ['name' => $key],
            ['value' => $this->serializeValue($value)],
        );
    }

    private function writePerSite(string $key, mixed $value, int $siteId): void
    {
        SiteSetting::query()
            ->withoutGlobalScope('belongs_to_site')
            ->updateOrCreate(
                ['name' => $key, 'site_id' => $siteId],
                ['value' => $this->serializeValue($value)],
            );
    }

    private function serializeValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }

    private function castValue(mixed $rawValue, ?string $type): mixed
    {
        if ($rawValue === null) {
            return null;
        }

        return match ($type) {
            'int', 'integer' => (int) $rawValue,
            'bool', 'boolean' => filter_var($rawValue, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $rawValue,
            'float', 'double' => (float) $rawValue,
            'array', 'json' => is_string($rawValue) ? (json_decode($rawValue, true) ?? $rawValue) : $rawValue,
            default => $rawValue,
        };
    }
}
