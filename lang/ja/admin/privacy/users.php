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
    'heading' => 'メンバープライバシーデータ',
    'description' => '指定したメンバーの個人データをエクスポート・削除します。コアおよびプラグインに登録された全プライバシーデータプロバイダーに対して 1 度の操作で fan-out されます。',

    'search' => [
        'label' => 'メンバーを検索',
        'placeholder' => 'メンバー ID・メール・アカウント名',
        'submit' => '検索',
        'help' => '数値 ID・完全一致のメール・完全一致のアカウント名で検索します。論理削除されたメンバーも含みます。',
        'not_found' => '該当するメンバーが見つかりません。',
    ],

    'subject' => [
        'heading' => '対象メンバー',
        'id' => 'ID',
        'email' => 'メール',
        'account_name' => 'アカウント名',
        'deleted_at' => '論理削除日時',
    ],

    'scope' => [
        'site' => '現サイトのみ',
        'network' => 'ネットワーク全体 (全サイト + グローバルテーブル)',
    ],

    'deletion_mode' => [
        'anonymize' => '匿名化 (識別情報を不可逆 HMAC に置換、行は保持)',
        'soft_delete' => '論理削除 (members.deleted_at をセット、子行は保持)',
        'hard_delete' => '物理削除 (関連する全行を削除)',
    ],

    'export' => [
        'heading' => 'エクスポート',
        'description' => 'プロバイダーごとにディレクトリを分けた ZIP と、実行内容を記述した manifest.json を生成します。',
        'download_site' => 'ダウンロード (現サイト)',
        'download_network' => 'ダウンロード (ネットワーク全体)',
    ],

    'delete' => [
        'heading' => '削除・匿名化',
        'description' => 'スコープとモードを選択して実行します。登録されている全プライバシーデータプロバイダーに対して fan-out されます。物理削除モードでも audit_logs と security_events は行自体を残して PII のみ匿名化し、追加専用ログのハッシュチェーンを保ちます。',
        'scope_label' => 'スコープ',
        'mode_label' => 'モード',
        'confirm_checkbox' => 'この操作が全プライバシーデータプロバイダー上のユーザーデータに影響し、不可逆である可能性があることを理解しました。',
        'confirm_prompt' => '選択したプライバシー操作を実行しますか？',
        'submit' => 'プライバシー操作を実行',
    ],

    'status' => [
        'deletion_completed' => 'プライバシー操作が完了しました: 削除 :deleted 件 / 匿名化 :anonymized 件 / エラー :errors 件。',
    ],

    'errors' => [
        'invalid_mode' => '選択された削除モードが認識できません。',
    ],
];
