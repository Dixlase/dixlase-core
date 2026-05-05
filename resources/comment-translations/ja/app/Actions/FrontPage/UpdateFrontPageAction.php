<?php

return [
    'Storage format can only be selected on initial creation. Existing value is used as-is when editing.' => '保存形式は初回作成時のみ選択可能。編集時は既存の値をそのまま使用する。',
    'If file storage, also save to file' => 'ファイル保存の場合はファイルにも保存',
    'Also save content to DB (serves as backup and revision source)' => 'DBにもコンテンツを保存（バックアップ兼リビジョンのソース）',
    'Record post-save state to revision (treated as manual since it\'s an explicit user save)' => '保存後の状態をリビジョンに記録（ユーザーの明示保存なので manual として扱う）',
    'Skip if no difference from previous revision' => '直前リビジョンと差分がない場合はスキップ',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Storage format can only be selected on initial creation. Existing value is used as-is when editing.' => 'machine',
        'If file storage, also save to file' => 'machine',
        'Also save content to DB (serves as backup and revision source)' => 'machine',
        'Record post-save state to revision (treated as manual since it\'s an explicit user save)' => 'machine',
        'Skip if no difference from previous revision' => 'machine',
    ],
];
