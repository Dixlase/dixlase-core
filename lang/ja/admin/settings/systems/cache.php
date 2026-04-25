<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * Website: https://exc-d.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

return [
    'heading' => 'キャッシュ管理',
    'title' => 'キャッシュクリア',
    'description' => 'アプリケーションの各種キャッシュをクリアできます',
    'config_cache' => [
        'name' => '設定キャッシュ',
        'description' => 'アプリケーションの設定ファイルのキャッシュをクリアします',
    ],
    'route_cache' => [
        'name' => 'ルートキャッシュ',
        'description' => 'ルーティング情報のキャッシュをクリアします',
    ],
    'view_cache' => [
        'name' => 'ビューキャッシュ',
        'description' => 'コンパイル済みビューファイルのキャッシュをクリアします',
    ],
    'application_cache' => [
        'name' => 'アプリケーションキャッシュ',
        'description' => 'アプリケーションで使用されるキャッシュデータをクリアします',
    ],
    'clear_confirm' => ':nameをクリアしますか？',
    'clear_all_title' => '一括キャッシュクリア',
    'clear_all_description' => '全てのキャッシュ（設定、ルート、ビュー、アプリケーション）を一度にクリアします。',
    'clear_all_warning' => 'この操作により、一時的にアプリケーションの動作が遅くなる場合があります。',
    'clear_all_button' => '全てのキャッシュをクリア',
    'clear_all_confirm' => '全てのキャッシュをクリアしますか？この操作により一時的にパフォーマンスが低下する可能性があります。',
    'info_config' => 'アプリケーションの設定ファイルをキャッシュして高速化します',
    'info_route' => 'ルーティング情報をキャッシュして高速化します',
    'info_view' => 'Bladeテンプレートをコンパイル済みPHPファイルとしてキャッシュします',
    'info_application' => 'アプリケーション内で使用される各種データのキャッシュです',
    'success_config' => '設定キャッシュをクリアしました',
    'success_route' => 'ルートキャッシュをクリアしました',
    'success_view' => 'ビューキャッシュをクリアしました',
    'success_application' => 'アプリケーションキャッシュをクリアしました',
    'success_all' => '全てのキャッシュをクリアしました',
    'error_invalid_type' => '無効なキャッシュタイプです',
    'error_general' => 'キャッシュクリア中にエラーが発生しました: :error',
];
