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
    'title' => 'プロフィール',
    'heading' => 'プロフィール設定',
    'description' => 'アカウント名、メールアドレス、言語設定、外観モード、二段階認証などの個人設定を管理します。',
    'use_system_default' => 'システムデフォルトを使用',
    'language_help' => '個別の言語設定です。未選択の場合はシステムのデフォルト言語が使用されます。',
    'account_name_help' => 'ログインに使用するアカウント名です。3〜20文字の半角英数字を使用してください。',
    'display_name_help' => '管理バーやプロフィールに表示される名前です。空欄の場合はアカウント名が表示されます。',
    'password_change_only' => 'パスワード（変更する場合のみ入力）',
    'updated' => 'プロフィールが更新されました。',
    'two_fa_updated' => '二段階認証設定が更新されました。',
    'login_notification_global_setting_help' => 'この設定はメンバー全体設定で制御されています。',
    'single_method_available' => '利用可能な認証方法',
    'submit' => 'プロフィールを更新',
    'updated_with_email_verification' => 'プロフィールが更新されました。<br>新しいメールアドレスに認証メールを送信しました。<br>メールを確認してメールアドレスの変更を完了してください。',
    'confirm_title' => 'プロフィール更新の確認',
    'confirm_message' => 'プロフィールを更新しますか？',
    'email_verification_success' => 'メールアドレスの変更が完了しました。',
    'account_verification_success' => 'アカウントの認証が完了しました。',
    'email_verification_invalid' => '認証リンクが無効です。',
    'email_already_verified' => 'このメールアドレスは既に認証済みです。',
    'pending_email_notice' => ':email への変更待ちです。送信された認証メールを確認して認証を完了してください。<br>メールが届いていない場合は、メールアドレスに間違いがないか、迷惑メールに入っていないか、ご確認ください。',
    'current_email' => '現在のメールアドレス: :email',
    'email_change_help' => 'メールアドレスを変更した場合、新しいメールアドレスに認証メールが送信されます。<br>認証が完了するまで変更は反映されません。',
    'email_change_help_no_mail' => 'メールアドレスを変更した場合、即時反映されます。',
    'updated_email_immediate' => 'プロフィールが更新されました。メールアドレスが変更されました。',
    'two_factor_requires_mail_server' => 'メールサーバーの設定とテストが完了していないため、二段階認証は使用できません。',
    'two_fa_management' => '二段階認証管理',
    'two_fa_disabled_notice' => '二段階認証管理を行うには、プロフィール設定で二段階認証を有効にしてください。',
    'passkey_disabled_notice' => 'パスキーが有効になっていないため、パスキーデバイスの管理はできません。',
    'passkey_no_devices_notice' => 'Passkey認証が有効になっていますが、まだデバイスが登録されていません。<a href=":url" class="underline font-semibold">二段階認証管理</a>でPasskeyデバイスを登録してください。',
    'passkey_registered' => 'Passkeyデバイスが正常に登録されました。',
    'passkey_deleted' => 'Passkeyデバイスを削除しました。',
    'passkey_deleted_all' => 'Passkeyデバイス（:count件）を削除しました。',
    'passkey_not_found' => 'Passkeyデバイスが見つかりません。',
    'passkey_delete_error' => 'Passkeyデバイスの削除に失敗しました。',
    'passkey_register_options_error' => 'Passkeyの登録を開始できませんでした。もう一度お試しください。',
    'passkey_register_error' => 'Passkeyデバイスの登録に失敗しました。',
    'all_passkeys_deleted' => 'Passkeyデバイス（:count件）を削除しました。',
    'no_passkeys_to_delete' => '削除するPasskeyデバイスがありません。',
    'passkey_delete_all_error' => 'Passkeyデバイスの削除に失敗しました。',
];
