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
    'heading' => 'キャッシュ管理',
    'title' => 'キャッシュクリア',
    'description' => 'アプリケーションの各種キャッシュをクリアできます',
    'config_cache' => [
        'name' => '設定キャッシュ',
        'description' => 'アプリケーションの設定ファイルのキャッシュをクリアします',
    ],
    'route_cache' => [
        'name' => 'ルートキャッシュ',
        'description' => 'ルーティング情報のキャッシュをクリアします',
    ],
    'view_cache' => [
        'name' => 'ビューキャッシュ',
        'description' => 'コンパイル済みビューファイルのキャッシュをクリアします',
    ],
    'application_cache' => [
        'name' => 'アプリケーションキャッシュ',
        'description' => 'アプリケーションで使用されるキャッシュデータをクリアします',
    ],
    'clear_confirm' => ':nameをクリアしますか？',
    'clear_all_title' => '一括キャッシュクリア',
    'clear_all_description' => '全てのキャッシュ（設定、ルート、ビュー、アプリケーション）を一度にクリアします。',
    'clear_all_warning' => 'この操作により、一時的にアプリケーションの動作が遅くなる場合があります。',
    'clear_all_button' => '全てのキャッシュをクリア',
    'clear_all_confirm' => '全てのキャッシュをクリアしますか？この操作により一時的にパフォーマンスが低下する可能性があります。',
    'rebuild' => '再生成',
    'rebuild_confirm' => ':nameを再生成しますか？',
    'rebuild_all_title' => '一括キャッシュ再生成',
    'rebuild_all_description' => '設定・ルート・ビューのキャッシュを一度に再生成します。',
    'rebuild_all_warning' => '本番環境向けの最適化です。再生成中は数秒のラグが発生する場合があります。',
    'rebuild_all_button' => '全てのキャッシュを再生成',
    'rebuild_all_confirm' => '全てのキャッシュを再生成しますか？',
    'rebuild_not_supported' => '※ アプリケーションキャッシュは Laravel の仕様により再生成できません（必要時に自動生成されます）',
    'info_config' => 'アプリケーションの設定ファイルをキャッシュして高速化します',
    'info_route' => 'ルーティング情報をキャッシュして高速化します',
    'info_view' => 'Bladeテンプレートをコンパイル済みPHPファイルとしてキャッシュします',
    'info_application' => 'アプリケーション内で使用される各種データのキャッシュです',
    'success_config' => '設定キャッシュをクリアしました',
    'success_route' => 'ルートキャッシュをクリアしました',
    'success_view' => 'ビューキャッシュをクリアしました',
    'success_application' => 'アプリケーションキャッシュをクリアしました',
    'success_all' => '全てのキャッシュをクリアしました',
    'success_rebuild_config' => '設定キャッシュを再生成しました',
    'success_rebuild_route' => 'ルートキャッシュを再生成しました',
    'success_rebuild_view' => 'ビューキャッシュを再生成しました',
    'success_rebuild_all' => '全てのキャッシュを再生成しました',
    'error_invalid_type' => '無効なキャッシュタイプです',
    'error_general' => 'キャッシュクリア中にエラーが発生しました: :error',
];
