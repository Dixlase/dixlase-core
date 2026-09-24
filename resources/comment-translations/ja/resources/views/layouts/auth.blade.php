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
    '{{-- Prevent FOUC: Dark mode + Alpine.js x-cloak (executed synchronously) --}}' => '{{-- FOUC防止：ダークモード + Alpine.js x-cloak（同期的に実行） --}}',
    '// Check whether the icon section holds an array (JSON)' => '// アイコンが配列形式（JSON）かチェック',
    '// Not an array, so treat it as a single icon' => '// 配列でない場合は単一アイコンとして扱う',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '// Not an array, so treat it as a single icon' => 'human',
        '// Check whether the icon section holds an array (JSON)' => 'human',
        '{{-- Prevent FOUC: Dark mode + Alpine.js x-cloak (executed synchronously) --}}' => 'machine',
    ],
];
