<?php

return [
    'Pass mail server settings state' => 'メールサーバー設定状態を渡す',
    'Check if 2FA can be enabled (on profile screen, check if mail server has been tested)' => '2FA有効化可能かをチェック（プロフィール画面ではメールサーバーテスト済みかチェック）',
    'Get value before saving (as integer) to detect changes in two-factor authentication mode' => '二段階認証モードの変更を検出するため、保存前の値を取得（整数値として）',
    'two_fa_mode is only overwritten when global settings is UseProfileSetting' => 'two_fa_mode は全体設定が UseProfileSetting のときだけ上書き',
    'Get 2FA state after saving (use value after saving)' => '保存後の2FA状態を取得（保存後の値を使用）',
    'Determine using TwoFaStatusService' => 'TwoFaStatusServiceを使用して判定',
    'Auto-generate if recovery codes do not exist' => '回復コードが存在しない場合は自動生成',
    'Show promotion modal if passkey is enabled and device is not registered' => 'パスキーが有効かつデバイス未登録の場合、促進モーダルを表示',
    'Add two-factor authentication settings' => '二段階認証設定の追加',
    'Get two-factor authentication methods enabled in global settings' => 'グローバル設定で有効な二段階認証方法を取得',
    '0=disabled, 1=enabled (default: enabled)' => '0=無効, 1=有効（デフォルト: 有効）',
    'Email authentication is always enabled, Passkey depends on settings' => 'メール認証は常に有効、Passkeyは設定に応じて',
    'Get Passkey device list' => 'Passkeyデバイス一覧を取得',
    'Get recovery code information' => '回復コード情報を取得',

    // ----- metadata (underscore-prefixed; ignored as translation entries) -----
    '_review_status' => [
        'Pass mail server settings state' => 'machine',
        'Check if 2FA can be enabled (on profile screen, check if mail server has been tested)' => 'machine',
        'Get value before saving (as integer) to detect changes in two-factor authentication mode' => 'machine',
        'two_fa_mode is only overwritten when global settings is UseProfileSetting' => 'machine',
        'Get 2FA state after saving (use value after saving)' => 'machine',
        'Determine using TwoFaStatusService' => 'machine',
        'Auto-generate if recovery codes do not exist' => 'machine',
        'Show promotion modal if passkey is enabled and device is not registered' => 'machine',
        'Add two-factor authentication settings' => 'machine',
        'Get two-factor authentication methods enabled in global settings' => 'machine',
        '0=disabled, 1=enabled (default: enabled)' => 'machine',
        'Email authentication is always enabled, Passkey depends on settings' => 'machine',
        'Get Passkey device list' => 'machine',
        'Get recovery code information' => 'machine',
    ],
];
