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
    'title' => 'コア整合性',
    'lead' => '署名済みマニフェストと照合し、Dixlase コアが純正・無改変のリリースかを検証します。これは「改ざん防止」ではなく「整合性・純正リリース保証」です。',

    'status' => [
        'genuine' => '純正',
        'modified' => '改変あり',
        'unsigned' => '未署名',
        'pending' => '検証待ち',
        'invalid' => '署名が無効',
        'error' => '検証エラー',
        'waived' => '免除済み',
    ],

    'desc' => [
        'genuine' => 'コアは署名済みマニフェストと完全に一致しています。',
        'modified' => 'コアは公式リリースですが、一部のファイルが署名時と異なります（多くは意図的なローカルカスタマイズ）。',
        'unsigned' => '署名済みマニフェストがありません — 開発ビルドです。',
        'pending' => '信頼する公開鍵が取得できません（オフライン、またはキャッシュ無し）。オンライン時に再試行してください。',
        'invalid' => 'マニフェストの署名を検証できませんでした。マニフェストが偽造、または信頼されない鍵で署名されている可能性があります。',
        'error' => '検証を完了できませんでした。下記メッセージを確認してください。',
        'waived' => '運用者がこのインストールの整合性警告を免除しています。',
    ],

    'field' => [
        'status' => '状態',
        'version' => 'バージョン',
        'key_id' => '鍵 ID',
        'signed_at' => '署名日時',
        'changed' => '変更ファイル',
    ],

    'diff' => [
        'modified' => '変更',
        'missing' => '欠落',
        'extra' => '追加',
    ],

    'changed_files_title' => '変更されたファイル (:count)',
    'recheck' => '今すぐ再チェック',

    'waiver_active' => [
        'title' => '有効な免除',
        'reason' => '理由',
        'by' => '免除者',
        'at' => '免除日時',
    ],

    'danger' => [
        'title' => 'デンジャーゾーン',
        'lead' => 'カスタマイズしたインストール向けの開発者操作です。このビルドの信頼はあなたの責任となります。',
        'disabled_notice' => '署名の免除／削除はこのインストールでは無効です。開発／カスタマイズ環境では DLS_CORE_ALLOW_UNSIGN=true（または APP_DEBUG=true）で有効化できます。',
    ],

    'waive' => [
        'title' => '署名チェックを免除',
        'lead' => '署名済みマニフェストは保持したまま整合性警告を抑制します。コアを意図的に改変した場合に使用します。',
        'reason_label' => '理由',
        'reason_placeholder' => '例：プロジェクト X 向けのローカルカスタマイズ',
        'button' => '署名チェックを免除',
        'confirm_title' => 'コアの署名チェックを免除しますか？',
        'confirm_message' => '免除を取り消すまで整合性警告は抑制されます。このビルドの責任を負うことになります。',
        'confirm_button' => '免除する',
    ],

    'unwaive' => [
        'button' => '免除を取り消す',
        'confirm_title' => 'コアの免除を取り消しますか？',
        'confirm_message' => '整合性警告が再び表示されます。',
        'confirm_button' => '取り消す',
    ],

    'remove' => [
        'title' => '署名を削除',
        'lead' => 'コアのマニフェストと署名を完全に削除します。ビルド環境で再署名するまでコアは「未署名」になります。',
        'button' => '署名を削除',
        'confirm_title' => 'コアの署名を削除しますか？',
        'confirm_message' => 'core-manifest.json と core-signature.sig を完全に削除します。コアは未署名として報告されます。',
        'confirm_button' => '削除する',
    ],

    'flash' => [
        'rechecked' => 'コア整合性を再チェックしました。',
        'waived' => 'コアの署名チェックを免除しました。',
        'already_waived' => 'コアの免除は既に有効です。',
        'unwaived' => 'コアの免除を取り消しました。',
        'signature_removed' => 'コアの署名を削除しました。コアは未署名になりました。',
    ],
];
