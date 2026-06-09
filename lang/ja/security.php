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
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE-COMMERCIAL, or contact info@dixlase.org).
 *
 * Unless you have entered into a commercial license agreement, this
 * file is governed by the AGPL terms below.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

return [
    // CAPTCHA関連
    'captcha_failover_subject' => '【警告】CAPTCHAサービス障害によるフェイルオーバー',
    'captcha_failover_message' => 'CAPTCHAサービスで障害が検出され、自動フェイルオーバーが実行されました。

切り替え元: :from
切り替え先: :to
発生日時: :time

元のサービスが復旧次第、自動的に切り戻されます。
状況を確認するには: php artisan captcha status',

    // 外部サービス障害
    'external_service_failure' => '外部サービス障害',
    'hibp_failure_subject' => '【警告】Have I Been Pwned API障害',
    'hibp_failure_message' => 'Have I Been Pwned APIへの接続に失敗しました。パスワード漏洩チェックは一時的にスキップされています。',

    // CAPTCHAバイパス通知
    'captcha_bypass_subject' => '【緊急】CAPTCHAバイパスが有効化されました',
    'captcha_bypass_message' => 'CAPTCHAバイパス（ブレークグラス）が有効化されました。

スコープ: :scope
理由: :reason
有効期間: :minutes 分
有効期限: :expires_at

これは緊急復旧機能です。復旧完了後は自動的に無効化されます。
手動で無効化するには: php artisan security:captcha-bypass disable',
];
