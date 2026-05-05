<?php

return [
    'Helper that converts datetimes into the display timezone and formats them.' => '日時を表示用タイムゾーンに変換してフォーマットするヘルパー。',
    'Named formats' => '名前付きフォーマット',
    'Convert the given datetime into the display timezone and format it' => '入力日時を表示用タイムゾーンに変換してフォーマットする',
    'Input value (null/empty string returns null)' => '入力値（null/空文字なら null を返す）',
    'PHP date format string or a FORMATS key' => 'PHP date format 文字列、または FORMATS のキー',
    'Convert the given datetime into a UTC ISO8601 string (for the HTML <time datetime> attribute)' => '入力日時を UTC の ISO8601 文字列に変換する（HTML <time datetime> 用）',
    'Return the display timezone ID' => '表示用タイムゾーン ID を返す',
    'Return a DateTimeZone object for the display timezone' => '表示用 DateTimeZone オブジェクトを返す',
    'Normalize the input into a CarbonImmutable instance' => '入力を CarbonImmutable に正規化する',
    'Dixlase always stores and calculates in UTC (config(\'app.timezone\')),' => 'Dixlase は保存・計算を常に UTC（config(\'app.timezone\')）で行い、',
    'and converts to site_settings.display_timezone only for display. This helper' => '表示時にのみ site_settings.display_timezone へ変換する。本ヘルパーは',
    'centralizes that display conversion and formatting. In Blade it is used as' => 'その表示変換とフォーマットを集約する。Blade では <x-ui-datetime> の',
    'the internal implementation of <x-ui-datetime>, so do not call directly from templates' => '内部実装として使われるため、テンプレートから直接呼ばないこと。',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Dixlase always stores and calculates in UTC (config(\'app.timezone\')),' => 'machine',
        'and converts to site_settings.display_timezone only for display. This helper' => 'machine',
        'centralizes that display conversion and formatting. In Blade it is used as' => 'machine',
        'the internal implementation of <x-ui-datetime>, so do not call directly from templates' => 'machine',
    ],
];
