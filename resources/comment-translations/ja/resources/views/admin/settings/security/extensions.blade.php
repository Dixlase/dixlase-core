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
    '{{-- Additional warning when required (shown only when toggle is ON) --}}' => '{{-- 必須にしている場合の追加警告（トグル ON 時のみ表示） --}}',
    '{{-- Authority URL disclosure (always shown regardless of toggle state) --}}' => '{{-- Authority URL の開示（トグル状態に関わらず常時表示） --}}',
    '{{-- form-toggle internally switches xModel to string \'0\'/\'1\', so
                         a simple truthy check would treat \'0\' as true.
                         To handle both initial value (boolean) and post-toggle (\'1\'/\'0\'),
                         we use numeric equality comparison --}}' => '{{-- form-toggle は内部で xModel を文字列 \'0\'/\'1\' に切り替えるため、
                         単純な truthy 判定だと \'0\' も真になってしまう。
                         初期値（boolean）と toggle 後（\'1\'/\'0\'）の両方に対応するため
                         数値的等価で比較する。 --}}',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '{{-- Additional warning when required (shown only when toggle is ON) --}}' => 'machine',
        '{{-- Authority URL disclosure (always shown regardless of toggle state) --}}' => 'machine',
        '{{-- form-toggle internally switches xModel to string \'0\'/\'1\', so
                         a simple truthy check would treat \'0\' as true.
                         To handle both initial value (boolean) and post-toggle (\'1\'/\'0\'),
                         we use numeric equality comparison --}}' => 'machine',
    ],
];
