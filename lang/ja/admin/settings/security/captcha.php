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
    'heading' => 'CAPTCHA設定',
    'title' => 'CAPTCHA設定',
    'description' => 'ボット対策のためのCAPTCHA（自動アクセス防止）設定を管理します。',
    'enabled' => 'CAPTCHAを有効にする',
    'driver' => 'CAPTCHAプロバイダー',
    'site_key' => 'サイトキー',
    'secret_key' => 'シークレットキー',
    'google_site_key' => 'Google reCAPTCHA サイトキー',
    'google_secret_key' => 'Google reCAPTCHA シークレットキー',
    'google_enterprise_site_key' => 'Google reCAPTCHA Enterprise サイトキー',
    'google_enterprise_secret_key' => 'APIキー',
    'google_project_id' => 'Google Cloud プロジェクト ID',
    'google_version' => 'reCAPTCHA バージョン',
    'google_min_score' => '最小スコア (0.0-1.0)',
    'turnstile_site_key' => 'Cloudflare Turnstile サイトキー',
    'turnstile_secret_key' => 'Cloudflare Turnstile シークレットキー',
    'min_score_description' => '0は最も疑わしく、1.0は最も信頼できることを示します。通常は0.5を推奨します。開発環境(localhost)では0に設定してください。',
    'validation_error' => 'CAPTCHA検証中にエラーが発生しました。',
    'button_disabled_reason' => 'キーエラーのため無効化されています',
    'enter_site_key' => 'サイトキーを入力してください',
    'invalid_v3_site_key' => 'v3サイトキーが無効です。正しいv3用のキーを入力してください。',
    'required_fields_missing' => '必要なフォーム要素が見つかりません',
    'required_fields_empty' => '必須フィールドが入力されていません',
    'enterprise_project_id_required' => 'reCAPTCHA EnterpriseにはプロジェクトIDが必要です',
    'unsupported_version' => 'サポートされていないreCAPTCHAバージョンです',
    'unsupported_provider' => 'サポートされていないCAPTCHAプロバイダーです',
    'authentication_success' => 'CAPTCHA認証が成功しました',
    'authentication_failed' => 'CAPTCHA認証に失敗しました',
    'v3_execution_failed' => 'reCAPTCHA v3の実行に失敗しました',
    'v3_script_load_failed' => 'reCAPTCHA v3スクリプトの読み込みに失敗しました',
    'v2_invisible_error' => 'reCAPTCHA v2 invisibleでエラーが発生しました',
    'v2_script_load_failed' => 'reCAPTCHA v2スクリプトの読み込みに失敗しました',
    'v2_checkbox_instruction' => 'チェックボックスをクリックして認証を完了してください。',
    'v2_checkbox_error' => 'reCAPTCHA v2 checkboxでエラーが発生しました',
    'v2_widget_render_failed' => 'reCAPTCHA v2ウィジェットのレンダリングに失敗しました',
    'turnstile_error' => 'Turnstileでエラーが発生しました',
    'turnstile_script_load_failed' => 'Turnstileスクリプトの読み込みに失敗しました',
    'enterprise_execution_failed' => 'reCAPTCHA Enterpriseの実行に失敗しました',
    'enterprise_script_load_failed' => 'reCAPTCHA Enterpriseスクリプトの読み込みに失敗しました',
    'test_completed_successfully' => 'CAPTCHAの認証テストが正常に完了しています。',
    'test_completed_hint' => '管理画面へのログインでCAPTCHAを使用するには<a href=":members_url" class="text-blue-600 dark:text-blue-400 hover:underline">メンバー全体設定</a>で有効にしてください。<br>プラグインでCAPTCHAを使用する場合は各プラグインの設定で有効にしてください。',
    'authentication_success_title' => '認証成功',
    'authentication_failed_title' => '認証失敗',
    'expired_message' => 'CAPTCHA認証が期限切れです。再度実行してください。',
    'site_key_project_id_missing' => 'サイトキーまたはプロジェクトIDが設定されていません',
    'server_communication_failed' => 'サーバーとの通信に失敗しました',
    'provider_settings_check' => 'の設定を確認',
    'test_required_title' => '認証テストが必要です',
    'test_required_description' => 'CAPTCHAを使用できるようにするには認証テストを行い、認証に成功する必要があります。',
    'provider_setup_link' => 'プロバイダー設定ページ',
    'test_unsupported_driver' => 'サポートされていないCAPTCHAドライバーです',
    'test_system_error' => 'システムエラーによりテストに失敗しました',
    'test_keys_missing' => 'サイトキーまたはシークレットキーが不足しています',
    'test_enterprise_keys_missing' => 'サイトキー、シークレットキー、またはプロジェクトIDが不足しています',
    'test_enterprise_config_invalid' => 'Google reCAPTCHA Enterpriseの設定が無効です',
    'test_enterprise_success' => 'Google reCAPTCHA Enterpriseの設定テストが成功しました',
    'test_enterprise_api_failed' => 'Enterprise APIテストに失敗しました',
    'test_api_request_failed' => 'APIリクエストが失敗しました。ステータス',
    'test_invalid_secret_key' => 'シークレットキーが無効です',
    'test_turnstile_success' => 'Cloudflare Turnstile接続テストが成功しました',
    'test_invalid_site_key_format' => 'サイトキーの形式が無効です。Google reCAPTCHAサイトキーは「6」で始まる40文字である必要があります。',
    'test_key_version_mismatch_v2_to_v3' => 'キータイプの不一致: v3が設定されていますが、これはv2キーのようです。reCAPTCHAバージョン設定を確認してください。',
    'test_key_version_mismatch_v3_to_v2' => 'キータイプの不一致: v2が設定されていますが、これはv3キーのようです。reCAPTCHAバージョン設定を確認してください。',
    'test_invalid_secret_verify' => 'シークレットキーが無効です。正しいシークレットキーを入力してください。',
    'test_bad_request' => 'リクエストが無効です。選択されたバージョンに対してリクエスト形式が正しくない可能性があります。',
    'form_settings' => 'CAPTCHAを使用するフォーム',
    'forms' => [
        'admin_login' => '管理画面ログイン',
    ],
    'turnstile_errors' => [
        'missing-input-secret' => 'シークレットパラメータが不足しています',
        'invalid-input-secret' => 'シークレットパラメータが無効または形式が正しくありません',
        'missing-input-response' => 'レスポンスパラメータが不足しています',
        'invalid-input-response' => 'レスポンスパラメータが無効または形式が正しくありません',
        'bad-request' => 'リクエストが無効または形式が正しくありません',
        'timeout-or-duplicate' => 'レスポンスが無効です：期限切れまたは既に使用されています',
        'internal-error' => 'レスポンス検証中に内部エラーが発生しました',
    ],
    'test_required' => 'CAPTCHA認証が完了してません。CAPTCHAを使用するには認証を実行して成功する必要があります。',
    'test_button' => '接続テスト',
    'test_status' => [
        'not_tested' => '接続テスト未実行。CAPTCHAを有効にする場合は、各項目を入力し、接続テストを完了させてください。',
        'not_tested_with_provider' => '接続テスト未実行。CAPTCHAを有効にする場合は、各項目を入力し、接続テストを完了させてください。<br>選択中: :provider （<a href=":link" target="_blank" class="text-blue-600 hover:text-blue-800 underline">設定方法を確認</a>）',
        'setup_link_text' => ':providerの設定を確認',
        'selected_provider' => '選択中',
        'passed_initial' => '接続テスト実行済み',
        'passed_success' => '接続テストが成功しました！保存するとCAPTCHAが使用できます。',
        'failed' => '接続テストに失敗しました。各項目やプロバイダの設定内容をご確認ください。',
        'testing' => '接続テスト中...',
    ],
    'live_validation' => 'CAPTCHA認証テスト',
    'live_validation_description' => 'CAPTCHAを有効にするには、保存前に認証テストを完了させてください。',
    'validation_required' => 'CAPTCHA認証が未完了です',
    'validate_button' => 'CAPTCHA認証を実行',
    'revalidate_button' => 'CAPTCHA認証を再実行',
    'tested_at' => '認証日時',
    'validation_success' => 'CAPTCHA認証が成功しました',
    'validation_failed' => 'CAPTCHA認証に失敗しました',
    'validation_required_before_save' => 'CAPTCHAが有効な場合、設定を保存する前にCAPTCHA認証を完了してください。',
    'test_validation' => 'CAPTCHAを使用するには認証テストが必要です',
    'test_validation_description' => '保存するとCAPTCHAを使用できます。',
    'validation_success_with_score' => 'CAPTCHA認証が成功しました (スコア: :score)。保存するとCAPTCHAが使用できるようになります。',
    'validation_score_too_low' => 'CAPTCHAスコアが低すぎます: :score (最小値: :min_score)',
    'validation_failed_with_errors' => 'CAPTCHA認証に失敗しました: :errors',
    'api_connection_failed' => 'reCAPTCHA APIへの接続に失敗しました',
    'driver_unsupported' => 'サポートされていないCAPTCHAドライバーです',
    'version_options' => [
        'v3' => 'v3 (推奨 - 非対話型)',
        'v2_checkbox' => 'v2 チェックボックス',
        'v2_invisible' => 'v2 非表示',
    ],
    'settings_updated' => 'CAPTCHA設定が更新されました。',
    'token_required' => 'CAPTCHAトークンが必要です',
    'secret_key_required' => 'シークレットキーが必要です',

    // フォーム設定
    'form_settings_title' => 'フォームごとのCAPTCHA設定',
    'form_settings_description' => 'CAPTCHAを適用するフォームを選択してください。コアとプラグインで定義されたすべてのフォームが表示されます。',
    'route' => 'ルート',
    'plugin' => 'プラグイン',
    'no_forms_available' => '利用可能なフォームがありません。',

    // カテゴリ
    'categories' => [
        'admin' => '管理画面',
        'users' => 'ユーザー',
        'contact' => 'お問い合わせ',
        'comment' => 'コメント',
    ],

    // フォーム名
    'forms' => [
        'admin_login' => '管理画面ログイン',
        'admin_password_reset' => '管理画面パスワードリセット',
        'admin_two_fa' => '管理画面二段階認証',
    ],
];
