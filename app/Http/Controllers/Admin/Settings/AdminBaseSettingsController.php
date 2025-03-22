<?php

/**
 * This file is part of MySoftware.
 *
 * Copyright (C) 2025 exc-D inc.
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

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminLoggedInController;
use App\Models\BaseSetting;
use App\Http\Requests\Admin\Settings\AdminSettingsSystemRequest;
use Illuminate\Support\Facades\Gate;

class AdminBaseSettingsController extends AdminLoggedInController
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

        // 権限を確認
        $this->checkPermission('super_admin');

        $settings = [
            'site_name' => BaseSetting::getValue('site_name', 'My Site'),
            'language' => BaseSetting::getValue('language', 'ja'),
            //'is_member_site' => BaseSetting::getValue('is_member_site', 'false'),
            //'allow_external_registration' => BaseSetting::getValue('allow_external_registration', 'false'),
            'maintenance_mode' => BaseSetting::getValue('maintenance_mode', 'false'),
            'maintenance_message' => BaseSetting::getValue('maintenance_message', '現在メンテナンス中です。しばらくお待ちください。'),
            //'allow_guest_registration' => BaseSetting::getValue('allow_guest_registration', 'false'),
            /*
            'required_fields' => BaseSetting::getValue('required_fields', [
                'address' => false,
                'phone' => false,
                'gender' => false,
                'birthday' => false,
            ]),
            */
        ];

        $this->viewParams['settings'] = $settings;

        return view(
            'admin::settings.base.index',
            $this->viewParams
        );
    }

    /**
     * 設定の更新
     */
    public function update(AdminSettingsSystemRequest $request)
    {
        // 権限を確認
        $this->authorize('super_admin');

        $settings = $request->only([
            'site_name',
            'language',
            'is_member_site',
            'allow_external_registration',
            'maintenance_mode',
            'maintenance_message',
            'allow_guest_registration',
        ]);

        // JSON形式の必須項目設定
        $requiredFields = $request->input('required_fields', []);

        BaseSetting::setValue('required_fields', $requiredFields);

        foreach ($settings as $name => $value) {
            BaseSetting::setValue($name, $value);
        }

        return redirect()->route('admin.settings.base')->with('success', '設定が更新されました。');
    }
}
