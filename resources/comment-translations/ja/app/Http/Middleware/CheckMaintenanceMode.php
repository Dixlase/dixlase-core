<?php

return [
    'Skip maintenance check if installation is not complete' => 'インストールが完了していない場合はメンテナンスチェックをスキップ',
    'Fallback to $_SERVER / $_ENV to handle env() returning null when config is cached' => 'config キャッシュ時の env() null 化対策として $_SERVER / $_ENV をフォールバック',
    'Admin panel and installation screen are always accessible' => '管理画面とインストール画面は常にアクセス可能',
    'Allow preview requests' => 'プレビューリクエストは通す',
    'Get maintenance mode settings' => 'メンテナンスモード設定を取得',
    'Proceed normally if maintenance mode is disabled' => 'メンテナンスモードが無効な場合は通常処理',
    'Check if not yet started when start datetime is set' => '開始日時が設定されている場合、まだ開始前かチェック',
    'Maintenance has not started yet' => 'まだメンテナンス開始前',
    'Check if already finished when end datetime is set' => '終了日時が設定されている場合、すでに終了しているかチェック',
    'Maintenance has finished (automatic release is handled by command)' => 'メンテナンス終了済み（自動解除処理はコマンドで行う）',
    'Display maintenance screen' => 'メンテナンス画面を表示',
    'Get maintenance settings' => 'メンテナンス設定を取得',
    'Calculate Retry-After header when auto-release is enabled and end datetime is set' => '自動解除が有効で終了日時が設定されている場合、Retry-Afterヘッダーを計算',
    'Pass context to display admin bar and banner if logged in as admin member' => '管理メンバーでログイン中なら、管理バーとバナーを表示するためのコンテキストを渡す',
    'Set Retry-After header' => 'Retry-Afterヘッダーを設定',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Skip maintenance check if installation is not complete' => 'machine',
        'Fallback to $_SERVER / $_ENV to handle env() returning null when config is cached' => 'machine',
        'Admin panel and installation screen are always accessible' => 'machine',
        'Allow preview requests' => 'machine',
        'Get maintenance mode settings' => 'machine',
        'Proceed normally if maintenance mode is disabled' => 'machine',
        'Check if not yet started when start datetime is set' => 'machine',
        'Maintenance has not started yet' => 'machine',
        'Check if already finished when end datetime is set' => 'machine',
        'Maintenance has finished (automatic release is handled by command)' => 'machine',
        'Display maintenance screen' => 'machine',
        'Get maintenance settings' => 'machine',
        'Calculate Retry-After header when auto-release is enabled and end datetime is set' => 'machine',
        'Pass context to display admin bar and banner if logged in as admin member' => 'machine',
        'Set Retry-After header' => 'machine',
    ],
];
