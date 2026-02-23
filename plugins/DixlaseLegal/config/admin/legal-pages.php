<?php

/**
 * This file is part of Dixlase Legal.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

/*
|--------------------------------------------------------------------------
| DixlaseLegal - Legal Page Types
|--------------------------------------------------------------------------
|
| 法務ページの種別定義。コアの空レジストリにマージされます。
| LegalPageService::loadPluginOverrides() で自動読み込みされ、
| admin.legal-pages 設定に統合されます。
|
*/

return [
    'privacy-policy' => [
        'name' => 'dixlase-legal::admin/legal-pages/page-types.privacy_policy.name',
        'description' => 'dixlase-legal::admin/legal-pages/page-types.privacy_policy.description',
        'required' => false,
        'icon' => 'fas fa-shield-alt',
    ],

    'terms-of-service' => [
        'name' => 'dixlase-legal::admin/legal-pages/page-types.terms_of_service.name',
        'description' => 'dixlase-legal::admin/legal-pages/page-types.terms_of_service.description',
        'required' => false,
        'icon' => 'fas fa-file-contract',
    ],

    'site-policy' => [
        'name' => 'dixlase-legal::admin/legal-pages/page-types.site_policy.name',
        'description' => 'dixlase-legal::admin/legal-pages/page-types.site_policy.description',
        'required' => false,
        'icon' => 'fas fa-globe',
    ],

    'cookie-policy' => [
        'name' => 'dixlase-legal::admin/legal-pages/page-types.cookie_policy.name',
        'description' => 'dixlase-legal::admin/legal-pages/page-types.cookie_policy.description',
        'required' => false,
        'icon' => 'fas fa-cookie-bite',
    ],

    'tokushoho' => [
        'name' => 'dixlase-legal::admin/legal-pages/page-types.tokushoho.name',
        'description' => 'dixlase-legal::admin/legal-pages/page-types.tokushoho.description',
        'required' => false,
        'icon' => 'fas fa-store',
    ],
];
