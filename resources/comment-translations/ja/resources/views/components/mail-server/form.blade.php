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
    Mail server settings form common component
    
    @param array $settings - Mail settings values
    @param array $mailers - Mailer options (optional)
    @param array $encryptions - Encryption options (optional)
    @param string $context - \'install\' or \'admin\' (default: \'admin\')
    @param string $admin_email - Administrator email address (install only)
--}}' => '{{--
    メールサーバー設定フォーム共通コンポーネント
    
    @param array $settings - メール設定値
    @param array $mailers - メーラー選択肢 (オプション)
    @param array $encryptions - 暗号化選択肢 (オプション)
    @param string $context - \'install\' または \'admin\' (デフォルト: \'admin\')
    @param string $admin_email - 管理者メールアドレス (インストール時のみ)
--}}',
    '{{-- CSP support: Pass settings via data attributes --}}' => '{{-- CSP対応: data属性で設定を渡す --}}',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        '{{--
    Mail server settings form common component
    
    @param array $settings - Mail settings values
    @param array $mailers - Mailer options (optional)
    @param array $encryptions - Encryption options (optional)
    @param string $context - \'install\' or \'admin\' (default: \'admin\')
    @param string $admin_email - Administrator email address (install only)
--}}' => 'machine',
        '{{-- CSP support: Pass settings via data attributes --}}' => 'machine',
    ],
];
