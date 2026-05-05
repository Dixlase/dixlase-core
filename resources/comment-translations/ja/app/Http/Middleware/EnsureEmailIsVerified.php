<?php

return [
    'Use member guard for admin panel' => '管理画面の場合はmemberガードを使用',
    'Note: For my page (user), use EnsureUserEmailIsVerified from the plugin side' => '※マイページ（user）の場合はプラグイン側のEnsureUserEmailIsVerifiedを使用',
    'If not verified' => '未認証の場合',
    'For admin panel, redirect to dedicated unverified page' => '管理画面の場合は専用の未認証ページへ',
    'If custom route is specified' => 'カスタムルートが指定されている場合',
    'Default redirects to home' => 'デフォルトはホームへリダイレクト',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Use member guard for admin panel' => 'machine',
        'Note: For my page (user), use EnsureUserEmailIsVerified from the plugin side' => 'machine',
        'If not verified' => 'machine',
        'For admin panel, redirect to dedicated unverified page' => 'machine',
        'If custom route is specified' => 'machine',
        'Default redirects to home' => 'machine',
    ],
];
