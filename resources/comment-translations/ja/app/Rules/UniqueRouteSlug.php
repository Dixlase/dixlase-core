<?php

return [
    'Route slug uniqueness validation rule' => 'ルートスラッグ一意性バリデーションルール',
    'Validates that top-level URL slugs do not duplicate across the entire system' => 'システム全体でトップレベルURLスラッグが重複しないことを検証する。',
    'Detects conflicts with reserved paths and other features, and returns appropriate error messages' => '予約パスとの競合、他機能との競合を検出し、適切なエラーメッセージを返す。',
    'Own owner ID (to exclude self)' => '自身のオーナーID（自分自身を除外するため）',
    'Execute validation' => 'バリデーション実行',
    'Static factory method' => '静的ファクトリーメソッド',
    'Owner ID (e.g., \'core:admin_url\')' => 'オーナーID（例: \'core:admin_url\'）',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Route slug uniqueness validation rule' => 'machine',
        'Validates that top-level URL slugs do not duplicate across the entire system' => 'machine',
        'Detects conflicts with reserved paths and other features, and returns appropriate error messages' => 'machine',
        'Own owner ID (to exclude self)' => 'machine',
        'Execute validation' => 'machine',
        'Static factory method' => 'machine',
        'Owner ID (e.g., \'core:admin_url\')' => 'machine',
    ],
];
