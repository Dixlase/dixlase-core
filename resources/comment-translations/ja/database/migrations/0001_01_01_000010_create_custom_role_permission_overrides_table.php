<?php

return [
    'Custom role permission overrides table' => 'カスタムロールの権限オーバーライドテーブル',
    'For permissions from the inherited preset role (base_role),' => '継承元プリセットロール（base_role）の権限に対して、',
    'defines individual permission grants or denials' => '個別の権限付与（grant）または拒否（deny）を定義する。',
    'Target custom role (foreign key constraint added in add_foreign_key_constraints)' => '対象カスタムロール（外部キー制約は add_foreign_key_constraints で追加）',
    'Permission enum value (e.g. members.view, media.upload)' => 'Permission enum 値（例: members.view, media.upload）',
    'Grant type: grant=explicitly allow / deny=explicitly deny' => '付与種別: grant=明示的に許可 / deny=明示的に拒否',
    'Updater (foreign key constraint added in add_foreign_key_constraints)' => '更新者（外部キー制約は add_foreign_key_constraints で追加）',
    'Only one entry per permission per role (including site scope)' => 'ロールごとに同一権限は1つだけ（site スコープ込み）',
    'Index' => 'インデックス',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Custom role permission overrides table' => 'machine',
        'For permissions from the inherited preset role (base_role),' => 'machine',
        'defines individual permission grants or denials' => 'machine',
        'Target custom role (foreign key constraint added in add_foreign_key_constraints)' => 'machine',
        'Permission enum value (e.g. members.view, media.upload)' => 'machine',
        'Grant type: grant=explicitly allow / deny=explicitly deny' => 'machine',
        'Updater (foreign key constraint added in add_foreign_key_constraints)' => 'machine',
        'Only one entry per permission per role (including site scope)' => 'machine',
        'Index' => 'machine',
    ],
];
