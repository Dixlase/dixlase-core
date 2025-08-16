<?php
/*
This file is part of MySoftware.

Copyright (C) 2025 exc-D inc.
https://exc-d.com

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

namespace Database\Seeders;

use App\Models\CaptchaFormSetting;
use Illuminate\Database\Seeder;

class CaptchaFormSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $formSettings = [
            [
                'key' => 'admin_login',
                'route_name' => 'admin.login',
                'enabled' => false,
                'plugin_name' => null,
                'is_core' => true,
                'display_order' => 1,
            ],
        ];

        foreach ($formSettings as $setting) {
            CaptchaFormSetting::createOrUpdate($setting);
        }
    }
}
