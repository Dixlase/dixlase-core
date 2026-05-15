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
    '{{--
    Mail test feature common component
    
    @param string $context - \'install\' or \'admin\' (default: \'admin\')
    @param string $connectionTestRoute - Route for connection test
    @param string $mailTestRoute - Route for mail sending test
    @param bool $showStatus - Whether to include test status display (default: false, for admin panel)
    @param array $testStatus - Test status data (for admin panel)
--}}' => '{{--
    メールテスト機能共通コンポーネント
    
    @param string $context - \'install\' または \'admin\' (デフォルト: \'admin\')
    @param string $connectionTestRoute - 接続テスト用ルート
    @param string $mailTestRoute - メール送信テスト用ルート
    @param bool $showStatus - テスト状態表示を含めるか (デフォルト: false、管理画面用)
    @param array $testStatus - テスト状態データ (管理画面用)
--}}',
    '{{-- CSP compliant: Pass settings via data attributes --}}' => '{{-- CSP対応: data属性で設定を渡す --}}',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '{{--
    Mail test feature common component
    
    @param string $context - \'install\' or \'admin\' (default: \'admin\')
    @param string $connectionTestRoute - Route for connection test
    @param string $mailTestRoute - Route for mail sending test
    @param bool $showStatus - Whether to include test status display (default: false, for admin panel)
    @param array $testStatus - Test status data (for admin panel)
--}}' => 'machine',
        '{{-- CSP compliant: Pass settings via data attributes --}}' => 'machine',
    ],
];
