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
 *       Dixlase Plugin and Theme Exception (see LICENSE
 *       for full exception terms); or
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
    'title' => '二段階認証管理',
    'passkey_devices' => 'Passkeyデバイス',
    'no_passkey_devices' => 'Passkeyデバイスが登録されていません',
    'add_passkey' => 'Passkeyを追加',
    'delete' => '削除',
    'delete_all' => '全て削除',
    'registered_at' => '登録日時',
    'last_used' => '最終使用',
    'passkey_info_title' => 'Passkeyについて',
    'passkey_info_1' => 'デバイスを登録すると、二段階認証で認証コードを入力する必要がなくなります。',
    'passkey_info_2' => '生体認証（指紋認証、顔認証など）またはデバイスのPINでログインできます。',
    'passkey_info_3' => 'Touch ID、Face ID、Windows Helloなどに対応しています。',
    'passkey_info_4' => '管理者はPasskeyデバイスの追加はできません。メンバー本人のみが実行できます。',
    'passkey_warning_title' => 'Passkeyデバイスが登録されていません',
    'passkey_warning_message' => 'Passkeyを使用するには、デバイスの登録が必要です。デバイスが登録されていない場合、Passkey認証は使用できません。',
    'passkey_warning_action' => '下の「Passkeyを追加」ボタンからデバイスを登録してください。',
    'device_count' => '登録済み: :current / :max 台',
    'max_devices_reached' => '登録可能なデバイス数の上限（:max台）に達しました。新しいデバイスを追加するには、既存のデバイスを削除してください。',
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
];
