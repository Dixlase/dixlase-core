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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms above.
 */

return [
    '{{-- Downloading modal (called by openModal from onlineThemes.download()) --}}' => '{{-- ダウンロード中モーダル（onlineThemes.download() から openModal で呼び出す） --}}',
    '{{-- Error and flash messages are displayed in <x-ui-flash-message /> in the admin layout --}}' => '{{-- エラー・フラッシュメッセージは管理レイアウトの <x-ui-flash-message /> で表示 --}}',
    '{{-- Thumbnail --}}' => '{{-- サムネイル --}}',
    '{{-- Uploading modal (called by openModal from @submit of ZIP upload form) --}}' => '{{-- アップロード中モーダル（ZIP アップロードフォームの @submit から openModal で呼び出す） --}}',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '{{-- Downloading modal (called by openModal from onlineThemes.download()) --}}' => 'machine',
        '{{-- Error and flash messages are displayed in <x-ui-flash-message /> in the admin layout --}}' => 'machine',
        '{{-- Thumbnail --}}' => 'machine',
        '{{-- Uploading modal (called by openModal from @submit of ZIP upload form) --}}' => 'machine',
    ],
];
