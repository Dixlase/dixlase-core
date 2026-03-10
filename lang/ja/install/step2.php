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
    'environment_title' => '環境設定',
    'environment_header' => 'アプリケーション環境の設定',
    'environment_description' => 'アプリケーションの動作環境を選択し、URLを設定してください。',

    // 環境設定関連
    'environment_settings' => '環境設定',
    'application_environment' => 'アプリケーション環境',
    'url_settings' => 'URL設定',
    'application_url_configuration' => 'アプリケーションURL設定',
    'admin_url_configuration' => '管理画面URL設定',
    'admin_panel_url' => '管理パネルURL',
    'timezone_configuration' => 'タイムゾーン設定',
    'application_timezone' => 'アプリケーションタイムゾーン',

    'app_env' => 'アプリケーション環境',
    'app_env_options' => [
        'local' => 'ローカル',
        'staging' => 'ステージング',
        'production' => '本番',
    ],

    'app_debug' => 'デバッグモード',
    'enable_debug' => 'デバッグモードを有効にする',
    'app_debug_note' => '本番環境ではデバッグモードは選択できません。',

    'app_url' => 'アプリケーションURL',
    'app_url_note' => '現在のホストに基づいて自動的に設定されます。必要に応じて変更してください。',

    // 管理画面URL
    'admin_url' => '管理画面URL',
    'admin_url_security_note' => '本番環境では管理画面URLは「admin」以外の予想されにくいURLを設定することを推奨します。',
    'admin_url_auto_generated' => '管理画面URLはセキュリティのためランダムな文字列で自動生成されます。',

    // SSL設定
    'force_ssl' => 'SSL（HTTPS）を強制する',

    // タイムゾーン
    'timezone' => [
        'label' => 'タイムゾーン',
    ],
    'timezone_note' => 'アプリケーションのデフォルトタイムゾーンを選択してください。',
];
