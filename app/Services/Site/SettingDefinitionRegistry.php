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

declare(strict_types=1);

namespace App\Services\Site;

use App\Enums\SettingScope;

/**
 * Central registry of setting definitions.
 *
 * Plugins and core code register their setting keys here so SettingResolver
 * can look up the scope (global / per-site / overridable) at read/write
 * time. Bound as a singleton so registrations performed during boot are
 * visible to all consumers in the same request.
 */
class SettingDefinitionRegistry
{
    /** @var array<string, SettingDefinition> */
    private array $definitions = [];

    /**
     * Register a single setting definition. Later registrations with the
     * same key replace earlier ones (last-wins).
     */
    public function register(SettingDefinition $definition): void
    {
        $this->definitions[$definition->name] = $definition;
    }

    /**
     * Register multiple definitions at once.
     */
    public function registerMany(SettingDefinition ...$definitions): void
    {
        foreach ($definitions as $definition) {
            $this->register($definition);
        }
    }

    /**
     * Look up a definition by key. Returns null if unregistered.
     */
    public function get(string $key): ?SettingDefinition
    {
        return $this->definitions[$key] ?? null;
    }

    /**
     * Whether the given key is registered.
     */
    public function has(string $key): bool
    {
        return isset($this->definitions[$key]);
    }

    /**
     * All registered definitions, indexed by key.
     *
     * @return array<string, SettingDefinition>
     */
    public function all(): array
    {
        return $this->definitions;
    }

    /**
     * Definitions matching the given scope.
     *
     * @return array<string, SettingDefinition>
     */
    public function filterByScope(SettingScope $scope): array
    {
        return array_filter(
            $this->definitions,
            fn (SettingDefinition $definition): bool => $definition->scope === $scope,
        );
    }
}
