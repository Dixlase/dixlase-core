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
 *       (see LICENSE.commercial, or contact office@exc-d.com).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms above.
 */

return [
    '{{--
    Log navigation partial
    
    @param string $logType - Current log type
    @param string|null $currentView - Current view for audit logs (\'db\' or \'file\')
    @param string $pageType - Page type (\'system\' or \'audit\')
--}}' => '{{--
    ログナビゲーションパーシャル
    
    @param string $logType - 現在のログタイプ
    @param string|null $currentView - 監査ログの現在のビュー（\'db\' or \'file\'）
    @param string $pageType - ページタイプ（\'system\' or \'audit\'）
--}}',
    '{{-- Browser category has only one subcategory so don\'t display it --}}' => '{{-- ブラウザカテゴリは小カテゴリが1つのみなので表示しない --}}',
    '{{-- Navigation for audit log page --}}' => '{{-- 監査ログページ用ナビゲーション --}}',
    '{{-- Navigation for file log page --}}' => '{{-- ファイルログページ用ナビゲーション --}}',
    '{{-- Only use horizontal navigation, so display nothing here --}}' => '{{-- 横のナビゲーションのみ使用するため、ここでは何も表示しない --}}',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '{{--
    Log navigation partial
    
    @param string $logType - Current log type
    @param string|null $currentView - Current view for audit logs (\'db\' or \'file\')
    @param string $pageType - Page type (\'system\' or \'audit\')
--}}' => 'machine',
        '{{-- Browser category has only one subcategory so don\'t display it --}}' => 'machine',
        '{{-- Navigation for audit log page --}}' => 'machine',
        '{{-- Navigation for file log page --}}' => 'machine',
        '{{-- Only use horizontal navigation, so display nothing here --}}' => 'machine',
    ],
];
