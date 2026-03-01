<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
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

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class ShortcodeServiceProvider extends ServiceProvider
{
    protected $shortcodes = [

    ];

    public function register()
    {
        $this->app->singleton('shortcode', function ($app) {
            return new \App\Services\ShortcodeManager($app);
        });
    }

    public function boot()
    {
        // ショートコードを登録
        $shortcode = $this->app['shortcode'];
        foreach ($this->shortcodes as $tag => $class) {
            $shortcode->add($tag, $class);
        }

        // Bladeディレクティブの登録
        Blade::directive('shortcode', function ($expression) {
            return "<?php echo shortcode_parse($expression); ?>";
        });
    }
}
