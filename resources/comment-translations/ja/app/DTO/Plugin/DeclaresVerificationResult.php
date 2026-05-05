<?php

return [
    'Declares section verification result DTO' => 'declares セクション検証結果DTO',
    'Verifies the declares section of plugin.json against the actual file structure' => 'plugin.json の declares セクションと実際のファイル構成を',
    'and represents the result' => '照合した結果を表現します。',
    'Number of declared items' => '宣言されたアイテム数',
    'Number of actually existing items' => '実際に存在するアイテム数',
    'Whether there are any issues' => '問題がないかどうか',
    'Get number of issues' => '問題数を取得',
    'Get total health deduction' => '健全性減点の合計を取得',
    '- Declared + file missing → -5' => '- 宣言あり + ファイルなし → -5',
    '- File exists + not declared → -2' => '- ファイルあり + 宣言なし → -2',
    'Detected issues' => '検出された問題',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Declares section verification result DTO' => 'machine',
        'Verifies the declares section of plugin.json against the actual file structure' => 'machine',
        'and represents the result' => 'machine',
        'Number of declared items' => 'machine',
        'Number of actually existing items' => 'machine',
        'Whether there are any issues' => 'machine',
        'Get number of issues' => 'machine',
        'Get total health deduction' => 'machine',
        '- Declared + file missing → -5' => 'machine',
        '- File exists + not declared → -2' => 'machine',
        'Detected issues' => 'human',
    ],
];
