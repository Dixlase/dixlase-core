<?php

return [
    // デフォルトメッセージ
    'default_message' => 'セキュリティ上の理由により、システムは現在ロックダウン中です。',

    // タイプ
    'types' => [
        'full' => '完全ロックダウン',
        'admin' => '管理画面ロックダウン',
        'api' => 'APIロックダウン',
        'login' => 'ログインロックダウン',
    ],

    // アクション
    'actions' => [
        'activated' => 'ロックダウン発動',
        'deactivated' => 'ロックダウン解除',
        'extended' => 'ロックダウン延長',
        'modified' => 'ロックダウン変更',
        'auto_released' => '自動解除',
    ],

    // エラーページ
    'error_title' => 'システムロックダウン中',
    'error_message' => 'セキュリティ上の理由により、現在システムへのアクセスが制限されています。',
    'contact_admin' => '管理者にお問い合わせください。',
];
