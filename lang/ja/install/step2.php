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
    'admin_url_prefix' => 'URLプレフィックス',
    'admin_url_suffix' => 'URLサフィックス',
    'admin_url_security_note' => 'プレフィックスを選択し、サフィックスを入力してください。サフィックスは4文字以上（半角英小文字と数字のみ）です。',
    'admin_url_auto_generated' => '管理画面URLはセキュリティのためプレフィックスとサフィックスがランダムに自動生成されます。',

    // SSL設定
    'force_ssl' => 'SSL（HTTPS）を強制する',

    // タイムゾーン
    'timezone' => [
        'label' => 'タイムゾーン',
    ],
    'timezone_note' => 'アプリケーションのデフォルトタイムゾーンを選択してください。',
];
