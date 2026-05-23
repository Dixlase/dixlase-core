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
 *       (see LICENSE.commercial, or contact info@dixlase.org).
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
    'heading' => 'IPアクセス制御',
    'title' => 'IPアクセス制御設定',
    'description' => 'システムへのIPアドレスベースのアクセス制御を設定します。',
    'admin_access_control' => '管理画面IPアクセス制御設定',
    'admin_url' => '管理画面URL',
    'enable_allowed_admin_ips' => '特定のIPアドレスのみアクセスを許可',
    'allowed_admin_ips' => '許可IPアドレス',
    'allowed_admin_ips_list' => '許可IPアドレスリスト',
    'enable_blocked_admin_ips' => '特定のIPアドレスをブロック',
    'blocked_admin_ips' => 'ブロックIPアドレス',
    'blocked_admin_ips_list' => 'ブロックIPアドレスリスト',
    'admin_ip_help' => 'IPv4 / IPv6 アドレスまたは CIDR 記法で入力してください。1行に1つずつ記入してください。例: 192.168.1.1, 192.168.1.0/24, 2001:db8::/32',
    'front_access_control' => 'フロントIPアクセス制御設定',
    'enable_allowed_front_ips' => '特定のIPアドレスのみアクセスを許可',
    'allowed_front_ips' => '許可IPアドレス',
    'allowed_front_ips_list' => '許可IPアドレスリスト',
    'enable_blocked_front_ips' => '特定のIPアドレスをブロック',
    'blocked_front_ips' => 'ブロックIPアドレス',
    'blocked_front_ips_list' => 'ブロックIPアドレスリスト',
    'front_ip_help' => 'IPv4 / IPv6 アドレスまたは CIDR 記法で入力してください。1行に1つずつ記入してください。例: 192.168.1.1, 192.168.1.0/24, 2001:db8::/32',
    'ip_list_placeholder' => '192.168.1.1
192.168.1.0/24
10.0.0.0/8',
    'settings_updated' => 'IPアクセス制御設定が更新されました。',

    'detected_ip_label' => 'アプリが認識しているあなたのIPアドレス',
    'detected_ip_hint' => '自分のアクセスを維持するため、有効化する前にこのアドレスが許可リストに含まれていることを確認してください。',
    'proxy_warning_heading' => 'リバースプロキシを検出しましたが TRUSTED_PROXIES が未設定です',
    'proxy_warning_heading_misconfigured' => 'リバースプロキシを検出しましたが、設定されている TRUSTED_PROXIES が実際のプロキシをカバーしていません',
    'proxy_warning_current_value' => '現在の TRUSTED_PROXIES: :value',
    'proxy_warning_body' => 'アプリはすべての訪問者を :proxy として認識しているため、IP許可・ブロックリストが実際のクライアントIPと一致しません。:proxy 自体を許可リスト・ブロックリストのいずれにも追加しないでください。許可リストに追加すると全アクセスを許可してしまい、ブロックリストに追加すると全アクセスをブロックしてしまいます。',
    'proxy_warning_howto' => '上記の行を環境変数として設定してください。設定場所は Laravel の `.env` ファイル、docker-compose の `environment:`（または `env_file:`）セクション、systemd ユニットの `Environment=` ディレクティブ、Apache の `SetEnv` など、お使いのデプロイ方式に応じた場所のいずれかです。保存後、`php artisan config:cache` を実行し（Docker をお使いの場合はアプリケーションコンテナ内で）、このページを再読み込みしてください。',
    'lockout_allowlist' => '現在のあなたのIPアドレス（:ip）が許可リストに含まれていません。このまま保存すると管理画面にアクセスできなくなります。先に :ip をリストに追加してください。',
    'lockout_blocklist' => '現在のあなたのIPアドレス（:ip）がブロックリストに含まれています。このまま保存すると管理画面にアクセスできなくなります。',

    'static_ip_recommendation' => '許可リストを有効化する場合、固定IP、または既知のオフィスやVPNの範囲（CIDR表記）の利用を強くおすすめします。動的IPは予告なく変わるとアクセスできなくなる可能性があります。',

    'invalid_entries' => '次のエントリは有効な IPv4 / IPv6 アドレスまたは CIDR 範囲ではありません: :entries',
];
