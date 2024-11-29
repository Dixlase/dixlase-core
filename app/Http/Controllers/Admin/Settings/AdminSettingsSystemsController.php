<?php

/**
 * This file is part of Your Software Name.
 *
 * Copyright (C) 2024 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Http\Controllers\Admin\Settings;



use App\Http\Controllers\Admin\AdminController;
use App\Models\SettingSystem;
use App\Http\Requests\Admin\Settings\AdminSettingsSystemRequest;

class AdminSettingsSystemsController extends AdminController
{

    //初期設定を行う
    public function __construct()
    {
        // 親クラスのコンストラクタを呼び出す
        parent::__construct();
    }

    /**
     * 設定の表示
     */

    public function index()
    {
        $settings = [
            'site_name' => SettingSystem::getValue('site_name', 'My Site'),
            'admin_theme' => SettingSystem::getValue('admin_theme', 'light'),
            'is_member_site' => SettingSystem::getValue('is_member_site', 'false'),
            'allow_external_registration' => SettingSystem::getValue('allow_external_registration', 'false'),
            'maintenance_mode' => SettingSystem::getValue('maintenance_mode', 'false'),
            'maintenance_message' => SettingSystem::getValue('maintenance_message', '現在メンテナンス中です。しばらくお待ちください。'),
            'allow_guest_registration' => SettingSystem::getValue('allow_guest_registration', 'false'),
            'required_fields' => SettingSystem::getValue('required_fields', [
                'address' => false,
                'phone' => false,
                'gender' => false,
                'birthday' => false,
            ]),
        ];

        $this->view_params['settings'] = $settings;
        $this->view_params['title'] = 'admin.settings.systems';

        return view(
            'admin.settings.systems.index',
            $this->view_params
        );
    }

    /**
     * 設定の更新
     */
    public function update(AdminSettingsSystemRequest $request)
    {

        $settings = $request->only([
            'site_name',
            'admin_theme',
            'is_member_site',
            'allow_external_registration',
            'maintenance_mode',
            'maintenance_message',
            'allow_guest_registration',
        ]);

        // JSON形式の必須項目設定
        $required_fields = $request->input('required_fields', []);

        SettingSystem::setValue('required_fields', $required_fields);

        foreach ($settings as $name => $value) {
            SettingSystem::setValue($name, $value);
        }

        return redirect()->route('admin.settings.systems')->with('success', '設定が更新されました。');
    }
}
