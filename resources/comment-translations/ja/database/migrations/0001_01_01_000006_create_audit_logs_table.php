<?php

return [
    '{' => '{',
    '}' => '}',
    'Plugin information (null for Core operations)' => 'プラグイン情報（コア操作ならnull）',
    'Optional additional information (JSON)' => '任意の追加情報（JSON）',
    'Hash chain columns (for tamper detection)' => 'ハッシュチェーン用カラム（改ざん検知）',
    'Hash of this record (SHA-256, 64 characters)' => 'このレコードのハッシュ（SHA-256、64文字）',
    'Hash of previous record (for chain formation)' => '前レコードのハッシュ（チェーン形成用）',
    'Chain sequence number (serial, used for verification)' => 'チェーンシーケンス番号（連番、検証時に使用）',
    'Hash algorithm (recorded for future changes)' => 'ハッシュアルゴリズム（将来の変更に備えて記録）',
    'Verification status (last verification result)' => 'Verification status（最後の検証結果）',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '{' => 'machine',
        '}' => 'machine',
        'Plugin information (null for Core operations)' => 'machine',
        'Optional additional information (JSON)' => 'machine',
        'Hash chain columns (for tamper detection)' => 'machine',
        'Hash of this record (SHA-256, 64 characters)' => 'machine',
        'Hash of previous record (for chain formation)' => 'machine',
        'Chain sequence number (serial, used for verification)' => 'machine',
        'Hash algorithm (recorded for future changes)' => 'machine',
        'Verification status (last verification result)' => 'machine',
    ],
];
