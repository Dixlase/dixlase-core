<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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


namespace Database\Seeders\Dev;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Plugin;

class PluginsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        /*
        Plugin::insert([
            [
                'name' => 'EventsPlugin',
                'directory' => 'EventsPlugin',
                'namespace' => 'Plugins\EventsPlugin',
                'version' => '1.0',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'PagesPlugin',
                'directory' => 'PagesPlugin',
                'namespace' => 'Plugins\PagesPlugin',
                'version' => '1.0',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'ResavationsPlugin',
                'directory' => 'ResavationsPlugin',
                'namespace' => 'Plugins\ResavationsPlugin',
                'version' => '1.0',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'UsersPlugin',
                'directory' => 'UsersPlugin',
                'namespace' => 'Plugins\UsersPlugin',
                'version' => '1.0',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
        */
    }
}
