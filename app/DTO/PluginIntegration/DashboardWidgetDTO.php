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

namespace App\DTO\PluginIntegration;

/**
 * Dashboard Widget DTO
 *
 * Holds widget data that the plugin displays on the dashboard
 */
final readonly class DashboardWidgetDTO
{
    /**
     * @param  string  $key  Widget unique key (e.g., 'pages_count')
     * @param  string  $label  Display label (translated string)
     * @param  string|int  $value  Main display value (e.g., '12', 0)
     * @param  string  $icon  Font Awesome icon class (e.g., 'fas fa-file-alt')
     * @param  string|null  $url  Link to detail page (nullable)
     * @param  string|null  $description  Supplementary description (nullable)
     * @param  string  $color  Card color ('blue', 'green', 'purple', 'orange', etc.)
     */
    public function __construct(
        public string $key,
        public string $label,
        public string|int $value,
        public string $icon,
        public ?string $url = null,
        public ?string $description = null,
        public string $color = 'blue',
    ) {}
}
