<?php

return [
    'Ignore JS/CSS for non-HTML editors' => 'HTML エディタ以外は JS/CSS を無視',
    'When saving to file, also save to the file' => 'ファイル保存の場合はファイルにも保存',
    'Always save content to DB as well (backup)' => '常にDBにもコンテンツを保存（バックアップ）',
    'Record revision on initial creation (treated as manual since it\'s an explicit user save)' => '初回作成時のリビジョンを記録（ユーザーの明示保存なので manual として扱う）',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Ignore JS/CSS for non-HTML editors' => 'machine',
        'When saving to file, also save to the file' => 'machine',
        'Always save content to DB as well (backup)' => 'machine',
        'Record revision on initial creation (treated as manual since it\'s an explicit user save)' => 'machine',
    ],
];
