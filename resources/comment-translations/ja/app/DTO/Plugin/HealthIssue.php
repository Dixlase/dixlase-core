<?php

return [
    '@internal For Core use only. Do not reference from plugins/themes' => '@internal コア専用。プラグイン/テーマから参照しないこと',
    'DTO representing an issue detected by health check' => '健全性チェックで検出された問題を表すDTO',
    'Issue type (corresponds to getDeductionRules() keys)' => '問題の種別（getDeductionRules()のキーに対応）',
    'Severity (critical, warning, info)' => '重要度（critical, warning, info）',
    'Issue description' => '問題の説明',
    'Deduction value (negative integer)' => '減点値（負の整数）',
    'Whether it is a critical issue' => '致命的な問題かどうか',
    'Detection evidence' => '検出根拠',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '@internal For Core use only. Do not reference from plugins/themes' => 'machine',
        'DTO representing an issue detected by health check' => 'machine',
        'Issue type (corresponds to getDeductionRules() keys)' => 'machine',
        'Severity (critical, warning, info)' => 'machine',
        'Issue description' => 'machine',
        'Deduction value (negative integer)' => 'machine',
        'Whether it is a critical issue' => 'machine',
        'Detection evidence' => 'human',
    ],
];
