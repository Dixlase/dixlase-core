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
    'heading' => '環境設定',
    'title' => '環境設定',
    'description' => 'アプリケーションの動作環境とデバッグモードを設定します。これらの設定は.envファイルに直接反映されます。',

    'app_env' => '動作環境',
    'app_env_help' => '動作環境を選択してください。本番環境では厳格なエラーハンドリングとセキュリティ設定が適用されます。',

    'env_options' => [
        'local' => 'ローカル（開発環境）',
        'staging' => 'ステージング（検証環境）',
        'production' => '本番環境',
    ],

    'env_descriptions' => [
        'local' => '開発者向けの環境です。詳細なエラー情報が表示され、デバッグに最適化されています。本番サーバーでは使用しないでください。',
        'staging' => '本番環境に近い検証環境です。本番リリース前のテストに使用します。一部のデバッグ機能が利用可能です。',
        'production' => '実際のユーザーが利用する本番環境です。セキュリティが最優先され、エラー詳細は非表示になります。',
    ],

    'app_debug' => 'デバッグモード',
    'app_debug_help' => 'デバッグモードを有効にすると、詳細なエラー情報やスタックトレースが表示されます。<strong class="text-red-600 dark:text-red-400">本番環境では必ず無効にしてください。</strong>',

    'debug_enabled_warning' => 'デバッグモードが有効です。詳細なエラー情報が表示されるため、セキュリティ上のリスクがあります。本番環境では無効にすることを強く推奨します。',
    'production_debug_warning' => '本番環境でデバッグモードを有効にすることはできません。デバッグモードを有効にすると、機密情報が漏洩する可能性があります。',

    'current_status' => '現在の状態',
    'current_env' => '現在の動作環境',
    'current_debug' => 'デバッグモード',

    'settings_updated' => '環境設定が更新されました。変更を完全に反映するには、ページを再読み込みしてください。',
    'update_failed' => '環境設定の更新に失敗しました。.envファイルの書き込み権限を確認してください。',

    'save_confirmation_title' => '環境設定の変更',
    'save_confirmation_message' => '環境設定を変更します。この変更はアプリケーション全体に影響します。続行しますか？',
];
