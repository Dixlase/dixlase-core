<?php

return [
    'Front page specific revision service (thin facade)' => 'フロントページ専用のリビジョンサービス（薄いファサード）',
    'Delegates actual logic to the generic {@see RevisionService}' => '実際のロジックは汎用の {@see RevisionService} に委譲する。',
    'Exists only to maintain backward compatibility for existing calls (Actions, Controllers, tests)' => '既存呼び出し（Actions, Controllers, テスト）の後方互換性を保つためにのみ存在する。',
    '/** @deprecated See {@see RevisionService::SETTING_KEY_RETENTION} */' => '/** @deprecated {@see RevisionService::SETTING_KEY_RETENTION} を参照 */',
    '/** @deprecated See {@see RevisionService::DEFAULT_RETENTION} */' => '/** @deprecated {@see RevisionService::DEFAULT_RETENTION} を参照 */',
    '/** @deprecated See {@see RevisionService::MAX_RETENTION} */' => '/** @deprecated {@see RevisionService::MAX_RETENTION} を参照 */',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Front page specific revision service (thin facade)' => 'machine',
        'Delegates actual logic to the generic {@see RevisionService}' => 'machine',
        'Exists only to maintain backward compatibility for existing calls (Actions, Controllers, tests)' => 'machine',
        '/** @deprecated See {@see RevisionService::SETTING_KEY_RETENTION} */' => 'machine',
        '/** @deprecated See {@see RevisionService::DEFAULT_RETENTION} */' => 'machine',
        '/** @deprecated See {@see RevisionService::MAX_RETENTION} */' => 'machine',
    ],
];
