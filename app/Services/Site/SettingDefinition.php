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

use App\Enums\SettingScope;

/**
 * Defines a setting key: where it lives, what its default is, and how it
 * is interpreted.
 *
 * Used by SettingDefinitionRegistry as the source of truth for whether a
 * given key is global / per-site / overridable, and consumed by
 * SettingResolver to pick the correct storage backend.
 */
final class SettingDefinition
{
    /**
     * @param  string  $name  Setting key (unique across the registry)
     * @param  SettingScope  $scope  Whether the value is global, per-site, or overridable
     * @param  mixed  $default  Default value when no row exists in either table
     * @param  string|null  $description  Human-readable description (admin UI hint)
     * @param  string|null  $type  Optional type hint for casting ('string', 'int', 'bool', 'array', etc.)
     */
    public function __construct(
        public readonly string $name,
        public readonly SettingScope $scope,
        public readonly mixed $default = null,
        public readonly ?string $description = null,
        public readonly ?string $type = null,
    ) {}

    /**
     * Whether this setting accepts a per-site override (Overridable or PerSite).
     */
    public function acceptsPerSite(): bool
    {
        return $this->scope !== SettingScope::Global;
    }

    /**
     * Whether this setting has a network-wide value (Overridable or Global).
     */
    public function acceptsGlobal(): bool
    {
        return $this->scope !== SettingScope::PerSite;
    }
}
