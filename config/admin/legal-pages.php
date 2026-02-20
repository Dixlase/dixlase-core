<?php

/**
 * This file is part of Dixlase.
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

return [
    /*
    |--------------------------------------------------------------------------
    | Legal Pages Registry
    |--------------------------------------------------------------------------
    |
    | 法務ページの種別定義。各プラグインは自身の config/admin/legal-pages.php で
    | エントリを追加またはオーバーライドできます。
    | required が true のページは URL 設定が必須となります。
    |
    */

    'privacy-policy' => [
        'name' => 'admin/settings/systems/legal-pages.privacy_policy.name',
        'description' => 'admin/settings/systems/legal-pages.privacy_policy.description',
        'required' => false,
        'icon' => 'fas fa-shield-alt',
    ],

    'terms-of-service' => [
        'name' => 'admin/settings/systems/legal-pages.terms_of_service.name',
        'description' => 'admin/settings/systems/legal-pages.terms_of_service.description',
        'required' => false,
        'icon' => 'fas fa-file-contract',
    ],

    'site-policy' => [
        'name' => 'admin/settings/systems/legal-pages.site_policy.name',
        'description' => 'admin/settings/systems/legal-pages.site_policy.description',
        'required' => false,
        'icon' => 'fas fa-globe',
    ],

    'cookie-policy' => [
        'name' => 'admin/settings/systems/legal-pages.cookie_policy.name',
        'description' => 'admin/settings/systems/legal-pages.cookie_policy.description',
        'required' => false,
        'icon' => 'fas fa-cookie-bite',
    ],
];
