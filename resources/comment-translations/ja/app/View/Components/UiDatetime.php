<?php

return [
    'render it as a <time> element.' => '<time> 要素として描画するコンポーネント。',
    'Usage:' => '使い方:',
    'Input datetime (interpreted as UTC)' => '入力日時（UTC として解釈）',
    'PHP date format or a DateTimeHelper::FORMATS key' => 'PHP date format か DateTimeHelper::FORMATS のキー',
    'Fallback label shown when the value is null or empty' => 'null/空文字時の代替表示',
    'Convert datetime stored in UTC to site_settings.display_timezone and' => 'UTC で保存された日時を site_settings.display_timezone に変換して',
    'The title is the same instant in UTC, so it can be matched against' => 'title は同じ時点を UTC で表したもので、サーバーのログと突き合わせられる。',
    'server logs; the view labels it as such.' => 'ビューではそのことを示すラベルを付ける。',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Convert datetime stored in UTC to site_settings.display_timezone and' => 'machine',
        'The title is the same instant in UTC, so it can be matched against' => 'machine',
        'server logs; the view labels it as such.' => 'machine',
    ],
];
