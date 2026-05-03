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
    // Passkeyデバイス未登録警告
    'device_not_registered_title' => 'Passkeyデバイスが登録されていません',
    'device_not_registered_message' => 'Passkey認証を使用するにはデバイスの登録が必要です。<br>プロフィール画面からデバイスを登録してください。<br>それまでは他の認証方法をご利用ください。',

    // Passkey認証
    'title' => 'Passkey認証(生体認証)',
    'prompt' => 'Passkey(生体認証)を使用してログインしてください。',
    'start_auth' => '認証を開始',
    'waiting_title' => 'Passkey認証(生体認証)待機中',
    'waiting_message' => 'Touch ID、Face ID、または登録済みのPasskeyを使用してください。',
    'success_title' => '認証成功',
    'success_message' => 'Passkey認証(生体認証)が完了しました。リダイレクトしています...',
    'error_title' => '認証失敗',
    'error_message' => 'Passkey認証に失敗しました。再試行してください。',
    'retry' => '再試行',
    'unsupported_title' => 'Passkey未対応',
    'unsupported_message' => 'お使いのデバイスまたはブラウザはPasskeyに対応していません。',
    'challenge_failed' => 'チャレンジの開始に失敗しました',
    'network_error' => 'ネットワークエラーが発生しました',
    'no_challenge_data' => 'チャレンジデータがありません',
    'verification_failed' => '認証の検証に失敗しました',
    'auth_cancelled' => '認証がキャンセルされました',
    'invalid_state' => '認証の状態が無効です',
    'auth_failed' => '生体認証に失敗しました',

    // 生体認証（Passkey）
    'https_required' => 'HTTPS接続が必要です。',
    'challenge_generation_failed' => 'チャレンジの生成に失敗しました。',
    'registered_successfully' => '生体認証を登録しました。',
    'registration_failed' => '生体認証の登録に失敗しました。',
    'revoked_successfully' => '生体認証を削除しました。',
    'not_found' => '生体認証が見つかりません。',
    'revocation_failed' => '生体認証の削除に失敗しました。',
    'all_revoked_successfully' => 'すべての生体認証を削除しました（:count件）。',
    'revoke_all_failed' => '生体認証の一括削除に失敗しました。',

    // パスキーデバイス名入力モーダル
    'device_name_title' => 'Passkeyデバイスの登録',
    'device_name_message' => 'このデバイスを識別するための名前を入力してください。',
    'device_name_label' => 'デバイス名',

    // パスキー登録促進モーダル
    'prompt_modal' => [
        'title' => 'Passkey(生体認証)の登録をおすすめします',
        'message' => 'Passkeyを登録すると、指紋認証や顔認証でより安全かつ便利にログインできます。',
        'register_now' => '今すぐ登録',
        'later' => '後で登録',
        'dont_show_again' => '今後この画面を表示しない',
        'dismissed' => 'パスキー登録促進モーダルを非表示に設定しました。',
        'reset' => 'パスキー登録促進モーダルの設定をリセットしました。',
    ],
];
