<?php

return [
    'Minimal contract for linkable content' => 'リンク可能なコンテンツの最小契約',
    'Used as foundation for plugin integration' => 'プラグイン間連携の基盤として使用します。',
    'Common interface for referencing content across multiple plugins' => 'メニュー、検索、タグ付けなど、複数のプラグインで',
    'such as menus, search, and tagging' => 'コンテンツを参照する際の共通インターフェースです。',
    'Get the unique ID (ULID/UUID) of the content' => 'コンテンツの一意なID（ULID/UUID）を取得',
    'Get the title of the content' => 'コンテンツのタイトルを取得',
    'Get the URL of the content' => 'コンテンツのURLを取得',
    'Get the type of the content' => 'コンテンツのタイプを取得',
    'e.g., \'post\', \'page\', \'media\', \'product\', \'inquiry\'' => '例: \'post\', \'page\', \'media\', \'product\', \'inquiry\'',
    'Get the source (provider) of the content' => 'コンテンツのソース（提供元）を取得',
    '- For Core: \'core\'' => '- コアの場合: \'core\'',
    '- For plugin: plugin slug (e.g., \'dixlase-blog\')' => '- プラグインの場合: プラグインスラッグ（例: \'dixlase-blog\'）',
    'Get the source table name of the content (optional)' => 'コンテンツのソーステーブル名を取得（オプション）',
    'Used for debugging and data integrity checks' => 'デバッグやデータ整合性チェックに使用',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Minimal contract for linkable content' => 'machine',
        'Used as foundation for plugin integration' => 'machine',
        'Common interface for referencing content across multiple plugins' => 'machine',
        'such as menus, search, and tagging' => 'machine',
        'Get the unique ID (ULID/UUID) of the content' => 'machine',
        'Get the title of the content' => 'machine',
        'Get the URL of the content' => 'machine',
        'Get the type of the content' => 'machine',
        'e.g., \'post\', \'page\', \'media\', \'product\', \'inquiry\'' => 'machine',
        'Get the source (provider) of the content' => 'machine',
        '- For Core: \'core\'' => 'machine',
        '- For plugin: plugin slug (e.g., \'dixlase-blog\')' => 'machine',
        'Get the source table name of the content (optional)' => 'machine',
        'Used for debugging and data integrity checks' => 'machine',
    ],
];
