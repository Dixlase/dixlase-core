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

    'integrity' => [
        'building_chains' => 'ハッシュチェーンを構築中...',
        'build_complete' => ':processed 件のログにハッシュチェーンを設定しました。残り: :remaining 件',
        'build_errors' => ':count 件のエラーが発生しました:',
        'verifying_chain' => 'ハッシュチェーンを検証中...',
        'chain_valid' => '✓ ハッシュチェーンは正常です。',
        'chain_invalid' => '✗ ハッシュチェーンに問題が検出されました！',
        'tampered_records' => '改ざんが検知されたレコード:',
        'and_more' => 'さらに :count 件...',
        'verifying_seal' => ':date の日次署名を検証中...',
        'seal_not_found' => '指定された日付の署名が見つかりません。',
        'seal_valid' => '✓ 日次署名は正常です。',
        'seal_invalid' => '✗ 日次署名に問題が検出されました！',
        'creating_seals' => '過去 :days 日分の日次署名を作成中...',
        'no_pending_seals' => '作成が必要な日次署名はありません。',
        'seals_created' => ':count 件の日次署名を作成しました。',
        'creating_seal' => ':date の日次署名を作成中...',
        'seal_created' => ':date の日次署名を作成しました（ログ数: :log_count）',
        'no_logs_for_date' => '指定された日付のログがありません。',
        'invalid_date' => '無効な日付形式です。YYYY-MM-DD形式で指定してください。',
        'stats_title' => '監査ログ整合性統計',
        'daily_seals_title' => '日次署名統計（過去30日間）',
        'stat_name' => '項目',
        'stat_value' => '値',
        'total' => '合計',
        'valid' => '正常',
        'invalid' => '異常',
        'total_logs' => '総ログ数',
        'with_hash' => 'ハッシュチェーン設定済み',
        'without_hash' => 'ハッシュチェーン未設定',
        'verified' => '検証済み',
        'tampered' => '改ざん検知',
        'unverified' => '未検証',
        'total_seals' => '総署名数',
        'valid_seals' => '正常な署名',
        'invalid_seals' => '異常な署名',
        'sealed_logs' => '署名済みログ数',
        'check' => 'チェック項目',
        'result' => '結果',
        'signature' => '署名',
        'log_count' => 'ログ件数',
        'final_hash' => '最終ハッシュ',
        'chain' => 'チェーン',
        'date' => '日付',
        'status' => 'ステータス',
        'unknown_action' => '不明なアクション: :action',
        'available_actions' => '利用可能なアクション:',
        'action_build' => 'ハッシュチェーンを構築',
        'action_verify' => 'ハッシュチェーンを検証',
        'action_seal' => '日次署名を作成',
        'action_stats' => '統計を表示',
    ],
];
