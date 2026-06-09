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

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ThemeList extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dls:theme:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display a list of all themes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $themes = DB::table('theme_settings')->get();

        if ($themes->isEmpty()) {
            $this->info(__('admin/command/theme-list.no_themes'));

            return;
        }

        $data = $themes->map(function ($theme) {
            return [
                'ID' => $theme->id,
                'Name' => $theme->name,
                'Directory' => $theme->directory,
                'Status' => $theme->enabled_at ? __('admin/command/theme-list.enabled') : __('admin/command/theme-list.disabled'),
            ];
        })->toArray();

        $this->table(['ID', 'Name', 'Directory', 'Status'], $data);
    }
}
