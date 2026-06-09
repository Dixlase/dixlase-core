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

namespace App\Contracts\Admin;

/**
 * Admin panel navigation manager interface
 *
 * Abstraction layer for merging plugin navigation settings into Core navigation
 * Eliminates direct dependency on AdminHelper and enables SDK separation
 */
interface AdminNavigationManagerInterface
{
    /**
     * Merge new-structure navigation file (config/admin/navigation.php)
     *
     * Supports ordering via _insert_before / _insert_after.
     * Does nothing if the file does not exist
     *
     * @param  string  $configFile  Plugin navigation settings file path
     */
    public function mergeNavigationFile(string $configFile): void;

    /**
     * Merge old-structure navigation settings (nav key in admin.php)
     *
     * Supports ordering via _insert_before / _insert_after.
     * Does nothing if the file does not exist or the nav key is not present
     *
     * @param  string  $configFile  Plugin admin settings file path
     */
    public function mergeNavConfig(string $configFile): void;
}
