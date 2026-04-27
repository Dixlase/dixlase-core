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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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

namespace App\Contracts\PluginIntegration;

use App\Contracts\Plugin\PluginCapabilityInterface;
use App\DTO\PluginIntegration\MenuDTO;

/**
 * Contract for plugins that provide navigation menus
 *
 * Enables themes to access menu data without directly referencing plugin internals.
 * Extends PluginCapabilityInterface for auto-discovery via PluginServiceResolver.
 */
interface MenuProviderInterface extends PluginCapabilityInterface
{
    /**
     * Get all active menus as select options (id => name)
     *
     * @return array<int|string, string>
     */
    public function getMenuOptions(): array;

    /**
     * Get a menu with its items by ID
     *
     * @param  int|string  $menuId  Menu identifier
     * @return MenuDTO|null Menu data, or null if not found
     */
    public function getMenu(int|string $menuId): ?MenuDTO;

    /**
     * Get all active menus with their items
     *
     * @return MenuDTO[]
     */
    public function getMenus(): array;
}
