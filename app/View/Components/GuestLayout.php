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

namespace App\View\Components;

use App\Services\Site\SettingResolver;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    protected $site_name;

    protected $appearance = 'light';

    protected $theme_class;

    protected $title = '';

    protected $view_params = [];

    protected $theme = '';

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        try {
            // Use default values before installation or in case of database connection error.
            // config('app.installed') is consulted first because env() returns
            // null after Laravel's config cache is built.
            if (! file_exists(base_path('.env')) || ! (config('app.installed', false) ?: env('INSTALLED', false))) {
                $this->site_name = env('APP_NAME', 'Dixlase');
                $this->theme = Config::get('admin.theme', 'light');
            } elseif (! Schema::hasTable('global_settings') || ! Schema::hasTable('site_settings')) {
                // Fall back to default if settings table does not exist (e.g. immediately after installation)
                $this->site_name = env('APP_NAME', 'Dixlase');
                $this->theme = Config::get('admin.theme', 'light');
            } else {
                // Retrieve via SettingResolver (site_name is PerSite, admin_theme is Global)
                $resolver = app(SettingResolver::class);
                $this->site_name = $resolver->get('site_name') ?: env('APP_NAME', 'Dixlase');
                $this->theme = $resolver->get('admin_theme') ?: Config::get('admin.theme', 'light');
            }
        } catch (\Throwable $e) {
            // Use default values in case of database connection error, etc.
            $this->site_name = env('APP_NAME', 'Dixlase');
            $this->theme = Config::get('admin.theme', 'light');
        }

        // Set the theme class
        if ($this->theme == 'light') {
            $this->theme_class = config('admin.theme_class_light');
        } else {
            $this->theme_class = config('admin.theme_class_dark');
        }
        $isDark = $this->theme === 'dark';

        $this->view_params = [
            'site_name' => $this->site_name,
            'theme' => $this->theme,
            'theme_class' => $this->theme_class,
            'isDark' => $isDark,
        ];

        return view(
            'layouts.guest',
            $this->view_params
        );
    }
}
