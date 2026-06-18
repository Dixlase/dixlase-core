<?php

/*
 * Japanese comment translations for the repository-root .gitignore.
 *
 * NOTE: .gitignore is plain text, not PHP, so it is NOT processed by the
 * dls:comment:build / convert-comments.sh pipeline (that pipeline is
 * PHP-AST-only and rewrites PHP comment tokens). This file is a manual
 * record kept here only to consolidate all translation material under
 * resources/comment-translations/. The canonical source comments in
 * .gitignore are English; the values below are their JA counterparts.
 *
 * Keys are the logical English comment text (multi-line wrapped comments
 * are joined into one key). Only comments that were originally Japanese
 * are recorded here.
 */

return [
    '--- Build artifacts (Vite output) ---' => '--- ビルド成果物（Vite 出力）を除外 ---',
    'Keep manually-managed assets such as images' => '画像など手動管理のアセットは残す',
    'Test SQLite database' => 'テスト用 SQLite データベース',
    'Laravel / PHP' => 'Laravel / PHP 関連',
    'Dixlase custom' => 'Dixlase カスタム',
    'Directory for custom override files' => 'カスタムオーバーライドファイルを入れるディレクトリ',
    'Plugin and theme directories' => 'プラグインとテーマのディレクトリ',
    'Plugins: each plugin is developed and shipped as its own repository, so Core never tracks plugin directories (same treatment as themes). A plugin downloaded/installed from the admin panel therefore does not surface as untracked files either.' => 'プラグイン: 各プラグインは個別のリポジトリとして開発・配布されるため、Core はプラグインディレクトリを一切追跡しない（テーマと同じ扱い）。管理画面からダウンロード／インストールした runtime のプラグインも未追跡として表面化しない。',
    'Themes: DixlaseOnePage is a vendor-managed directory expanded into themes/DixlaseOnePage/ via composer/installers, so Core does not track it (migrated Submodule -> Composer dependency in b451bc75).' => 'テーマ: DixlaseOnePage は composer/installers 経由で themes/DixlaseOnePage/ に展開される vendor 管理ディレクトリのため、Core では追跡しない（b451bc75 で Submodule → Composer 依存に移行）。',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '--- Build artifacts (Vite output) ---' => 'human',
        'Keep manually-managed assets such as images' => 'human',
        'Test SQLite database' => 'human',
        'Laravel / PHP' => 'human',
        'Dixlase custom' => 'human',
        'Directory for custom override files' => 'human',
        'Plugin and theme directories' => 'human',
        'Plugins: each plugin is developed and shipped as its own repository, so Core never tracks plugin directories (same treatment as themes). A plugin downloaded/installed from the admin panel therefore does not surface as untracked files either.' => 'human',
        'Themes: DixlaseOnePage is a vendor-managed directory expanded into themes/DixlaseOnePage/ via composer/installers, so Core does not track it (migrated Submodule -> Composer dependency in b451bc75).' => 'human',
    ],
];
