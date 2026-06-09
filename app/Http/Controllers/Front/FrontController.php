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

namespace App\Http\Controllers\Front;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class FrontController extends Controller
{
    //

    // Declare variables
    protected $siteName;

    protected $appearance = 'light';

    protected $viewParams = [];
    // protected $currentTheme = 'default';

    public function __construct()
    {
        // Get theme settings and pass to view
        $this->loadThemeSettings();
    }

    /**
     * Load theme settings
     */
    protected function loadThemeSettings(): void
    {
        try {
            // Get active theme
            $activeThemeId = DB::table('theme_settings')
                ->where('key', 'enabled_theme_id')
                ->value('value');

            if (! $activeThemeId) {
                $this->viewParams['themeSettings'] = (object) [];

                return;
            }

            // Get theme information
            $theme = DB::table('themes')->find($activeThemeId);
            if (! $theme) {
                $this->viewParams['themeSettings'] = (object) [];

                return;
            }

            // Generate theme-specific settings table name
            $settingsTableName = 'thm_'.strtolower(str_replace('-', '_', $theme->slug)).'_settings';

            // Get theme settings (column names are 'name' and 'value')
            $settings = DB::table($settingsTableName)
                ->get()
                ->pluck('value', 'name');

            // Convert to object and pass to view
            $this->viewParams['themeSettings'] = (object) $settings->toArray();
        } catch (\Exception $e) {
            // Pass empty object if error occurs
            \Log::error('Failed to load theme settings: '.$e->getMessage());
            $this->viewParams['themeSettings'] = (object) [];
        }
    }
}
