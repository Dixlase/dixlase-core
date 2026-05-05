<?php

return [
    'Decides what to do when a route exists but no translation is available' => 'ルートは存在するが、要求された locale の翻訳がない場合に',
    'for the requested locale.' => '何をするかを決定します。',
    'The core default implementation issues a 302 redirect to the same path' => 'コア既定実装は、同じパスをサイトの primary locale 配下へ',
    'under the site\'s primary locale. Any multilingual plugin (first-party,' => '302 リダイレクトします。任意の多言語プラグイン (コア提供、',
    'third-party, or a custom in-house implementation) — or an unrelated' => 'サードパーティ製、独自実装のいずれでも) や、',
    'plugin such as one that handles redirects — can rebind this contract' => 'redirects を扱う他のプラグインは、この contract を rebind して',
    'to return 404, render a fallback locale, or apply a custom redirect' => '404 を返したり、fallback locale を render したり、',
    'rule.' => '独自リダイレクトルールを適用したりできます。',
    'This contract only fires when a route is matched but content is missing' => 'この contract が発火するのは、ルートはマッチしたが現在 locale の',
    'for the current locale. Unmatched URLs continue to return a normal 404.' => 'コンテンツが無い場合のみ。マッチしない URL は通常通り 404 を返します。',
    'Produce the response for a request whose translation is missing.' => '翻訳が見つからない request に対する response を生成します。',
    'The current request, with locale already resolved' => '現在の request (locale は解決済み)',
    'The locale the visitor asked for (e.g. \'ja\')' => 'visitor が要求した locale (例: \'ja\')',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Decides what to do when a route exists but no translation is available' => 'human',
        'for the requested locale.' => 'human',
        'The core default implementation issues a 302 redirect to the same path' => 'human',
        'under the site\'s primary locale. Any multilingual plugin (first-party,' => 'human',
        'third-party, or a custom in-house implementation) — or an unrelated' => 'human',
        'plugin such as one that handles redirects — can rebind this contract' => 'human',
        'to return 404, render a fallback locale, or apply a custom redirect' => 'human',
        'rule.' => 'human',
        'This contract only fires when a route is matched but content is missing' => 'human',
        'for the current locale. Unmatched URLs continue to return a normal 404.' => 'human',
        'Produce the response for a request whose translation is missing.' => 'human',
        'The current request, with locale already resolved' => 'human',
        'The locale the visitor asked for (e.g. \'ja\')' => 'human',
    ],
];
