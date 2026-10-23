<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see LICENSE-EXCEPTIONS for
 *       full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms above.
 */

return [
    '<!-- Media selection modal -->' => '<!-- メディア選択モーダル -->',
    '<!-- Modal content -->' => '<!-- モーダルコンテンツ -->',
    '<!-- Header -->' => '<!-- ヘッダー -->',
    '<!-- Search and filter -->' => '<!-- 検索とフィルター -->',
    '<!-- Media grid -->' => '<!-- メディアグリッド -->',
    '<!-- Loaded dynamically by JavaScript -->' => '<!-- JavaScriptで動的に読み込み -->',
    '<!-- Footer -->' => '<!-- フッター -->',
    '<!-- Generated dynamically by JavaScript -->' => '<!-- JavaScriptで動的に生成 -->',
    '{{-- Page navigation lives in the footer, not under the grid, so it is visible without scrolling. From sm up the footer is a grid with two equal outer columns, so the navigation sits at the centre of the modal rather than midway between the count and the buttons; each item names its column, so the buttons stay right when the navigation is hidden. On a narrow screen the navigation takes a row of its own above the count and the buttons. --}}' => '{{-- ページ送りはグリッドの下ではなくフッターに置き、スクロールせずに見えるようにする。sm 以上ではフッターを左右の列が等幅のグリッドにし、ページ送りが件数とボタンの中間ではなくモーダルの中央に来るようにする。各要素は列を明示するので、ページ送りが隠れてもボタンは右に残る。狭い画面ではページ送りが件数とボタンの上に 1 行を取る。 --}}',
    '// Ignored: the selector uploads in place now; kept for existing callers' => '// 無視される。選択画面はその場でアップロードするようになったが、既存の呼び出し側のために残す',
    '// The same permission the media.store route checks (check.menu.edit:media)' => '// media.store ルートが確かめるのと同じ権限（check.menu.edit:media）',
    '{{-- In-modal upload. Opens from the Upload button, or by itself when files are dragged over the modal. Each file shows its progress or the server\'s reason for refusing it. --}}' => '{{-- モーダル内のアップロード。アップロードボタンで開くほか、ファイルをモーダルの上にドラッグすると自動で開く。ファイルごとに進み具合か、サーバーが拒否した理由を表示する。 --}}',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '<!-- Media selection modal -->' => 'machine',
        '<!-- Modal content -->' => 'machine',
        '<!-- Header -->' => 'machine',
        '<!-- Search and filter -->' => 'machine',
        '<!-- Media grid -->' => 'machine',
        '<!-- Loaded dynamically by JavaScript -->' => 'machine',
        '<!-- Footer -->' => 'machine',
        '<!-- Generated dynamically by JavaScript -->' => 'machine',
        '{{-- Page navigation lives in the footer, not under the grid, so it is visible without scrolling. From sm up the footer is a grid with two equal outer columns, so the navigation sits at the centre of the modal rather than midway between the count and the buttons; each item names its column, so the buttons stay right when the navigation is hidden. On a narrow screen the navigation takes a row of its own above the count and the buttons. --}}' => 'machine',
        '// Ignored: the selector uploads in place now; kept for existing callers' => 'machine',
        '// The same permission the media.store route checks (check.menu.edit:media)' => 'machine',
        '{{-- In-modal upload. Opens from the Upload button, or by itself when files are dragged over the modal. Each file shows its progress or the server\'s reason for refusing it. --}}' => 'machine',
    ],
];
