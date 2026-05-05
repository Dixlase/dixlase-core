<?php

return [
    'Front-end routes.' => 'フロントエンドルート。',
    'v0.1.0 ships without active locale URL routing: visiting / serves the' => 'v0.1.0 では locale URL ルーティングはアクティブにせず、/ にアクセスすると',
    'welcome page directly without a /{locale}/ redirect, and plugin web' => '/{locale}/ へのリダイレクトなしで welcome ページを直接返します。プラグインの',
    'routes mount at their declared paths without a locale prefix.' => 'web ルートは locale prefix なしで宣言されたパスにマウントされます。',
    'The locale infrastructure (LocaleHelper, SetFrontLocale middleware,' => 'locale インフラ (LocaleHelper、SetFrontLocale middleware、',
    'LocalizedUrlProvider / MissingTranslationHandler contracts, the' => 'LocalizedUrlProvider / MissingTranslationHandler contract、上記の',
    '/locale/switch endpoint above) is in place so any multilingual plugin' => '/locale/switch エンドポイント) は整備済みで、任意の多言語プラグイン',
    '(first-party, third-party, or a custom in-house implementation) that' => '(コア提供、サードパーティ製、または独自実装のいずれでも) が',
    'wires itself to the same contracts can opt-in by wrapping these' => '同じ contract に従って組み込めば、これらのルートを',
    'routes in a Route::prefix(\'{locale}\')->where(...)->middleware(SetFrontLocale)' => 'Route::prefix(\'{locale}\')->where(...)->middleware(SetFrontLocale) で',
    'group and registering its own Route::fallback() that redirects' => 'グループ化し、locale-less URL を redirect する独自の Route::fallback()',
    'locale-less URLs.' => 'を登録することで opt-in できます。',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Front-end routes.' => 'human',
        'v0.1.0 ships without active locale URL routing: visiting / serves the' => 'human',
        'welcome page directly without a /{locale}/ redirect, and plugin web' => 'human',
        'routes mount at their declared paths without a locale prefix.' => 'human',
        'The locale infrastructure (LocaleHelper, SetFrontLocale middleware,' => 'human',
        'LocalizedUrlProvider / MissingTranslationHandler contracts, the' => 'human',
        '/locale/switch endpoint above) is in place so any multilingual plugin' => 'human',
        '(first-party, third-party, or a custom in-house implementation) that' => 'human',
        'wires itself to the same contracts can opt-in by wrapping these' => 'human',
        'routes in a Route::prefix(\'{locale}\')->where(...)->middleware(SetFrontLocale)' => 'human',
        'group and registering its own Route::fallback() that redirects' => 'human',
        'locale-less URLs.' => 'human',
    ],
];
