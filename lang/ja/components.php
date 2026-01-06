<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * Website: https://exc-d.com
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
    // ページネーション関連
    'pagination' => [
        'navigation' => 'ページナビゲーション',
        'page' => ':current ページ / 全 :total ページ',
        'previous' => '前へ',
        'next' => '次へ',
        'first' => '最初',
        'last' => '最後',
        'showing' => ':first から :last を表示（全 :total 件）',
        'per_page' => '表示件数',
        'per_page_label' => '表示件数',
        'total_count' => '全:total件',
        'total_pages' => '全 :count ページ',
        'no_results' => '該当するデータがありません',
        'items_suffix' => '件',
        'sort_by' => '並び替え',
        'asc' => '昇順',
        'desc' => '降順',
        'ascending' => '昇順（小→大、古→新）',
        'descending' => '降順（大→小、新→古）',
    ],


    // フォーム関連
    'forms' => [
        'placeholder' => [
            'search' => '検索キーワードを入力...',
            'email' => 'メールアドレスを入力',
            'password' => 'パスワードを入力',
            'name' => '名前を入力',
            'title' => 'タイトルを入力',
            'description' => '説明を入力',
        ],
        'validation' => [
            'required' => 'この項目は必須です',
            'email' => '有効なメールアドレスを入力してください',
            'unique' => 'この値は既に存在しています',
            'min_length' => '最低 :min 文字以上で入力してください',
            'max_length' => '最大 :max 文字以内で入力してください',
            'confirmed' => 'パスワード確認が一致しません',
        ],
    ],

    // フィルター関連
    'filters' => [
        'search_keyword' => 'キーワード',
        'role_filter' => '権限フィルター',
        'status_filter' => 'ステータスフィルター',
        'clear_button' => 'クリア',
    ],

    // ステータス関連
    'status' => [
        'active' => '有効',
        'inactive' => '無効',
        'enabled' => '有効',
        'disabled' => '無効',
        'draft' => '下書き',
        'published' => '公開',
        'scheduled' => '日付指定',
        'pending' => '保留中',
        'approved' => '承認済み',
        'rejected' => '却下',
        'cancelled' => 'キャンセル',
        // 説明
        'draft_description' => '下書き状態です。公開されません。',
        'published_description' => '即座に公開されます。',
        'scheduled_description' => '指定した日時に公開されます。',
    ],

    // メッセージ関連
    'messages' => [
        'success' => '操作が正常に完了しました',
        'loading' => '読み込み中...',
        'no_data' => 'データがありません',
        'confirm_delete' => '本当に削除しますか？',
        'unsaved_changes' => '保存されていない変更があります',
    ],

    // テーブル関連
    'table' => [
        'no_data' => 'データがありません',
        'select_all' => 'すべて選択',
        'selected_count' => ':count 件選択中',
        'sort_asc' => '昇順でソート',
        'sort_desc' => '降順でソート',
        'caption' => 'データ一覧',
        'unknown_role' => '不明なロール',
    ],

    // モーダル関連
    'modal' => [
        'delete_title' => '削除の確認',
        'delete_message' => 'この操作は取り消せません。本当に削除しますか？',
    ],

    // メールアドレス入力関連
    'email_input' => [
        'confirmation_label' => 'メールアドレス（確認）',
        'confirmation_help' => 'コピー＆ペーストは無効です。手入力で確認してください。',
        'match_status' => 'メールアドレス一致状態',
        'match_success' => '一致しています',
        'match_error' => '一致していません',
    ],

    // パスワードツール関連
    'password_messages' => [
        'toolbar_label' => 'パスワードツール',
        'strength' => [
            'error' => 'パスワードが条件を満たしていません',
            'normal' => '普通の強度',
            'strong' => '強いパスワード',
        ],
        'tooltip' => [
            'generate' => '自動生成',
            'copy' => 'コピー',
            'toggle' => '表示切替',
        ],
        'copied' => 'パスワードがコピーされました！',
        'requirements' => [
            // 表示用（固定文言）
            'length' => '8文字以上',
            'lowercase' => '小文字を1文字以上含む',
            'number' => '数字を1文字以上含む',
            'uppercase' => '大文字を1文字以上含む',
            'symbol' => '記号（!@#$%^&* など）を1文字以上含む',

            // 可変メッセージ（パラメータ付き）
            'length_full' => ':min文字以上（推奨 :recommended 文字以上）',
            'length_simple' => ':min文字以上',

            // 任意の場合の特別メッセージ
            'lowercase_optional_note' => '小文字を含む',
            'number_optional_note' => '数字を含む',
            'uppercase_optional_note' => '大文字を含む',
            'symbol_optional_note' => '記号（!@#$%^&*-_=+など）',

            // 強度ラベル
            'weak' => '弱い',
            'normal' => '普通',
            'strong' => '強い',
            'very_strong' => '非常に強い',
        ],
        'error' => 'パスワードが条件を満たしていません。',
    ],

    // 外観モード選択
    'appearance_mode' => [
        'auto' => '自動',
        'light' => 'ライト',
        'dark' => 'ダーク',
        'auto_description' => 'システムの設定に従います',
        'light_description' => 'ライトモードで表示',
        'dark_description' => 'ダークモードで表示',
    ],

    // ログイン通知設定
    'login_notification' => [
        'label' => 'ログイン通知モード',
        'help' => 'ログイン時にメール通知を送信するタイミングを設定します。',
        'global_setting_help' => 'この設定は全体設定で制御されており、変更できません。',
        'options' => [
            'disabled' => '無効',
            'different_device' => '異なるデバイス・IPでのログイン時のみ通知',
            'always' => '常に通知',
            'use_profile_setting' => 'プロフィール設定に従う',
        ],
    ],

    // 二段階認証設定
    'two_fa' => [
        'mode_label' => '二段階認証モード',
        'help' => '二段階認証を有効にすると、ログイン時に追加の認証が必要になります。',
        'method_label' => '二段階認証方法',
        'email_always_enabled' => 'メール認証は常に有効です',
        'passkey' => 'パスキー',
        'passkey_disabled_globally' => '全体設定で無効になっています',
        'method_note' => '二段階認証方法は全体設定で管理されています。',
        'change_in_global_settings' => '全体設定で変更',
        'default_method' => 'デフォルトの認証方法',
        'passkey_disabled_default_email_only' => 'パスキーが無効の場合、メール認証のみ使用できます。',
        'default_method_help' => 'ログイン時に最初に使用する認証方法を選択します。',
        'options' => [
            'disabled' => '無効',
            'different_device' => '異なるデバイス・IPでのログイン時',
            'always' => '常に有効',
            'use_profile_setting' => 'プロフィール設定に従う',
        ],
    ],

    // 二段階認証管理
    'two_fa_management' => [
        'title' => '二段階認証管理',
        'passkey_devices' => 'Passkeyデバイス',
        'no_passkey_devices' => 'Passkeyデバイスが登録されていません',
        'add_passkey' => 'Passkeyを追加',
        'delete' => '削除',
        'delete_all' => '全て削除',
        'registered_at' => '登録日時',
        'last_used' => '最終使用',
        'passkey_info_title' => 'Passkeyについて',
        'passkey_info_1' => 'Passkeyは生体認証やPINを使用した安全な認証方法です',
        'passkey_info_2' => '複数のデバイスを登録できます',
        'passkey_info_3' => 'デバイスごとに名前を付けて管理できます',
        'recovery_codes_title' => '回復コード',
        'recovery_codes_remaining' => '残り :count 個の回復コードがあります',
        'recovery_codes_not_generated' => '回復コードが生成されていません',
        'recovery_codes_regenerate' => '回復コードを再生成',
        'recovery_codes_generate' => '回復コードを生成',
        'recovery_codes_info_title' => '回復コードについて',
        'recovery_codes_info_1' => '回復コードは二段階認証デバイスにアクセスできない場合に使用します',
        'recovery_codes_info_2' => '各コードは1回のみ使用できます',
        'recovery_codes_info_3' => '安全な場所に保管してください',
        'recovery_codes_info_4' => 'コードを紛失した場合は再生成できます',
        'recovery_codes_info_5' => '再生成すると古いコードは無効になります',
        'recovery_codes_info_6' => '定期的に新しいコードを生成することを推奨します',
        // JavaScript用メッセージ
        'passkey_not_supported' => 'お使いのブラウザはPasskeyに対応していません',
        'passkey_register_success' => 'Passkeyの登録に成功しました',
        'passkey_register_error' => 'Passkeyの登録に失敗しました',
        'passkey_cancelled' => 'Passkeyの登録がキャンセルされました',
        'passkey_already_registered' => 'このPasskeyは既に登録されています',
        'passkey_delete_success' => 'Passkeyの削除に成功しました',
        'passkey_delete_error' => 'Passkeyの削除に失敗しました',
        'passkey_delete_all_error' => '全てのPasskeyの削除に失敗しました',
        'confirm_delete_passkey' => '本当にこのPasskeyを削除しますか？',
        'recovery_codes_error' => '回復コードの生成に失敗しました',
        // 信頼済みデバイス管理
        'trusted_devices_title' => '信頼済みデバイス',
        'no_trusted_devices' => '信頼済みデバイスはありません',
        'unknown_device' => '不明なデバイス',
        'ip_address' => 'IPアドレス',
        'trusted_devices_info_title' => '信頼済みデバイスについて',
        'trusted_devices_info_1' => '信頼済みデバイスでは二段階認証がスキップされます',
        'trusted_devices_info_2' => 'セキュリティのため、定期的に見直すことを推奨します',
        'trusted_devices_info_3' => '不要なデバイスは削除してください',
        'confirm_delete_trusted_device' => '本当にこの信頼済みデバイスを削除しますか？',
        'trusted_device_delete_success' => '信頼済みデバイスの削除に成功しました',
        'trusted_device_delete_error' => '信頼済みデバイスの削除に失敗しました',
        'trusted_device_delete_all_error' => '全ての信頼済みデバイスの削除に失敗しました',
        // モーダルタイトル
        'confirm_delete_trusted_device_title' => '信頼済みデバイスの削除',
        'confirm_delete_trusted_device_message' => '本当にこの信頼済みデバイスを削除しますか？',
        'confirm_delete_all_trusted_devices_title' => '全ての信頼済みデバイスを削除',
        'confirm_delete_all_trusted_devices_message' => '本当に全ての信頼済みデバイスを削除しますか？',
        'confirm_delete_passkey_title' => 'Passkeyの削除',
        'confirm_delete_passkey_message' => '本当にこのPasskeyを削除しますか？',
        'confirm_delete_all_passkeys_title' => '全てのPasskeyを削除',
        'confirm_delete_all_passkeys_message' => '本当に全てのPasskeyを削除しますか？',
        'recovery_codes_confirm_title' => '回復コードの生成',
        'recovery_codes_confirm_message' => '回復コードを生成しますか？既存のコードは無効になります。',
        'confirm_delete_recovery_codes_title' => '回復コードの削除',
        'confirm_delete_recovery_codes_message' => '本当に全ての回復コードを削除しますか？',
        'recovery_codes_delete_success' => '回復コードの削除に成功しました',
        'recovery_codes_delete_error' => '回復コードの削除に失敗しました',
    ],
];
