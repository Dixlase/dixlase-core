<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
 *
 *   (b) a commercial license agreement obtained from exc-D inc.
 *       (see LICENSE.commercial, or contact office@exc-d.com).
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

    /*
    |--------------------------------------------------------------------------
    | Authority API URL
    |--------------------------------------------------------------------------
    |
    | Dixlase 公式の鍵管理サイトの URL。プラグイン署名の公開鍵を取得するために
    | 使用する。本番では https://keys.dixlase.com を指定（既定）。
    | 開発環境で別の Authority を使う場合のみ env で上書きする。
    |
    */
    'url' => env('DIXLASE_AUTHORITY_URL', 'https://keys.dixlase.com'),

    /*
    |--------------------------------------------------------------------------
    | キャッシュ有効期間（時間）
    |--------------------------------------------------------------------------
    |
    | ローカル DB にキャッシュした公開鍵をどれくらい信頼するか。
    | この時間を超えた鍵は次回検証時に再フェッチを試みる（失敗しても古い
    | キャッシュで続行する）。
    |
    */
    'cache_ttl_hours' => (int) env('DIXLASE_AUTHORITY_CACHE_TTL_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | フェッチタイムアウト（秒）
    |--------------------------------------------------------------------------
    |
    | 公開鍵取得の HTTP タイムアウト。短すぎるとネットワーク遅延で失敗、
    | 長すぎるとプラグインインストールが遅延する。
    |
    */
    'fetch_timeout_seconds' => (int) env('DIXLASE_AUTHORITY_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | SSL 証明書検証
    |--------------------------------------------------------------------------
    |
    | サンドボックス・社内検証で self-signed cert の Authority を使う場合のみ
    | false にする。本番では必ず true（CA 検証あり）。
    |
    */
    'verify_ssl' => filter_var(env('DIXLASE_AUTHORITY_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),

];
