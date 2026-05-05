<?php

return [
    'Core default behaviour for unmatched translations: 302 redirect to the' => '翻訳が見つからない場合のコア既定挙動: 同じパスをサイトの',
    'same path under the site\'s primary locale.' => 'primary locale 配下へ 302 リダイレクトします。',
    'Any multilingual plugin (first-party, third-party, or a custom' => '任意の多言語プラグイン (コア提供、サードパーティ製、または独自実装の',
    'in-house implementation) — or another extension such as a redirects' => 'いずれでも) や、redirects プラグインなどのその他の拡張機能は、',
    'plugin — can rebind MissingTranslationHandler to replace this with a' => 'MissingTranslationHandler を rebind して、これを 404、',
    '404, a fallback render, or custom redirect rules.' => 'fallback render、または独自リダイレクトルールに置き換えられます。',
    'If the requested locale already matches the fallback, returning' => '要求された locale が既に fallback と一致する場合、',
    'another redirect to the same URL would loop. Surface a 404 so' => '同じ URL への再リダイレクトはループになるため、404 を返して',
    'upstream sees there genuinely is no content.' => '上位層に「本当にコンテンツが無い」と伝えます。',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Core default behaviour for unmatched translations: 302 redirect to the' => 'human',
        'same path under the site\'s primary locale.' => 'human',
        'Any multilingual plugin (first-party, third-party, or a custom' => 'human',
        'in-house implementation) — or another extension such as a redirects' => 'human',
        'plugin — can rebind MissingTranslationHandler to replace this with a' => 'human',
        '404, a fallback render, or custom redirect rules.' => 'human',
        'If the requested locale already matches the fallback, returning' => 'human',
        'another redirect to the same URL would loop. Surface a 404 so' => 'human',
        'upstream sees there genuinely is no content.' => 'human',
    ],
];
