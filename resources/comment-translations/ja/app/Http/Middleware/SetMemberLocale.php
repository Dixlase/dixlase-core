<?php

return [
    'Process only if .env file exists, installation is complete, and in admin panel' => '.envファイルが存在し、インストール済みかつ管理画面の場合のみ処理',
    'Check authentication state' => '認証状態を確認',
    'Prioritize member\'s individual language settings, use system default if null or empty' => 'メンバーの個別言語設定を優先、nullまたは空の場合はシステムデフォルト',
    'Get value if Enum, use as-is if string' => 'Enumの場合は値を取得、文字列の場合はそのまま使用',
    'Use system default if member settings are not available' => 'メンバー設定がない場合はシステムデフォルト',
    'Check if language is available' => '利用可能な言語かチェック',
    'Fallback to system default if language is invalid' => '無効な言語の場合はシステムデフォルトにフォールバック',
    'Get language from session if on installation screen' => 'インストール画面の場合はセッションから言語を取得',
    'Use default language if session error occurs' => 'セッションエラーの場合はデフォルト言語を使用',
    'Log and skip if database error or other errors occur' => 'データベースエラーやその他のエラーが発生した場合はログに記録してスキップ',
    'Fallback: set default language' => 'フォールバック: デフォルト言語を設定',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Process only if .env file exists, installation is complete, and in admin panel' => 'machine',
        'Check authentication state' => 'machine',
        'Prioritize member\'s individual language settings, use system default if null or empty' => 'machine',
        'Get value if Enum, use as-is if string' => 'machine',
        'Use system default if member settings are not available' => 'machine',
        'Check if language is available' => 'machine',
        'Fallback to system default if language is invalid' => 'machine',
        'Get language from session if on installation screen' => 'machine',
        'Use default language if session error occurs' => 'machine',
        'Log and skip if database error or other errors occur' => 'machine',
        'Fallback: set default language' => 'machine',
    ],
];
