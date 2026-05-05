<?php

return [
    'FK added together in add_foreign_key_constraints (999999) (cascade to front_pages.id)' => 'FK は add_foreign_key_constraints (999999) でまとめて追加（front_pages.id への cascade）',
    'Complete snapshot of all fields (title, content, custom_js, custom_css, storage_type, editor_type, status, etc.)' => '全フィールド（title, content, custom_js, custom_css, storage_type, editor_type, status など）の完全スナップショット',
    'FK added together in add_foreign_key_constraints (999999) (nullOnDelete to members.id)' => 'FK は add_foreign_key_constraints (999999) でまとめて追加（members.id への nullOnDelete）',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'FK added together in add_foreign_key_constraints (999999) (cascade to front_pages.id)' => 'machine',
        'Complete snapshot of all fields (title, content, custom_js, custom_css, storage_type, editor_type, status, etc.)' => 'machine',
        'FK added together in add_foreign_key_constraints (999999) (nullOnDelete to members.id)' => 'machine',
    ],
];
