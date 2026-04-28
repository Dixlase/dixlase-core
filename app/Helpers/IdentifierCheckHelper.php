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

namespace App\Helpers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * ログイン識別子確認ヘルパー
 *
 * メールアドレスまたはアカウント名の存在確認を行う共通機能
 * 管理者ログインとユーザーログインの両方で使用可能
 */
class IdentifierCheckHelper
{
    /**
     * 識別子確認を実行（レート制限付き）
     *
     * @param  string  $login  ログイン識別子（メールアドレスまたはアカウント名）
     * @param  string  $ipAddress  IPアドレス
     * @param  string  $userModelClass  ユーザーモデルクラス名
     * @param  array  $settings  ロックアウト設定
     * @param  string  $context  コンテキスト（'admin' または 'user'）
     * @return array ['exists' => bool, 'has_passkey' => bool, 'user' => Model|null]
     *
     * @throws ValidationException
     */
    public static function checkWithRateLimit(
        string $login,
        string $ipAddress,
        string $userModelClass,
        array $settings,
        string $context = 'admin'
    ): array {
        // レート制限が無効の場合はスキップ
        if (! ($settings['enabled'] ?? false)) {
            return self::performCheck($login, $ipAddress, $userModelClass, $context);
        }

        return self::performCheck($login, $ipAddress, $userModelClass, $context);
    }

    /**
     * 識別子確認の実行
     *
     * @param  string  $login  ログイン識別子
     * @param  string  $ipAddress  IPアドレス
     * @param  string  $userModelClass  ユーザーモデルクラス名
     * @param  string  $context  コンテキスト
     *
     * @throws ValidationException
     */
    protected static function performCheck(
        string $login,
        string $ipAddress,
        string $userModelClass,
        string $context
    ): array {
        // タイミング攻撃対策：常に一定時間待機（100-300ms）
        $delayMs = random_int(100, 300);
        usleep($delayMs * 1000);

        // ログイン識別子モードに基づくユーザー検索
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL) !== false;

        if ($context === 'admin') {
            // 管理者コンテキスト: LoginIdentifierModeに基づいて検索フィールドを制限
            $mode = \App\Enums\LoginIdentifierMode::tryFrom(
                (int) \App\Models\SecuritySetting::getValue('login_identifier_mode', \App\Enums\LoginIdentifierMode::EmailOrAccountName->value)
            ) ?? \App\Enums\LoginIdentifierMode::EmailOrAccountName;

            if ($isEmail && $mode->supportsEmail()) {
                $user = $userModelClass::where('email', $login)->first();
            } elseif (! $isEmail && $mode->supportsAccountName()) {
                $user = $userModelClass::where('account_name', $login)->first();
            } else {
                $user = null;
            }
        } else {
            // ユーザーコンテキスト: メールアドレスまたはアカウント名で検索
            $user = $userModelClass::where('email', $login)
                ->orWhere('account_name', $login)
                ->first();
        }

        if ($user) {
            // ユーザー存在確認成功
            AuditLog::logAuth(AuditLog::ACTION_LOGIN_IDENTIFIER_CHECK, [
                'severity' => AuditLog::SEVERITY_INFO,
                'outcome' => AuditLog::OUTCOME_SUCCESS,
                'actor' => $user,
                'context' => [
                    'login_identifier' => $login,
                    'identifier_type' => filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'account_name',
                    'check_context' => $context,
                ],
            ]);

            Log::info('Login identifier check successful', [
                'user_id' => $user->id,
                'login' => $login,
                'ip' => $ipAddress,
                'context' => $context,
            ]);

            // パスキー登録状況を確認
            $hasPasskey = self::hasPasskey($user);

            return [
                'exists' => true,
                'has_passkey' => $hasPasskey,
                'user' => $user,
            ];
        } else {
            // ユーザー存在確認失敗（セキュリティログ）
            AuditLog::logSecurity(AuditLog::ACTION_LOGIN_IDENTIFIER_NOT_FOUND, [
                'severity' => AuditLog::SEVERITY_WARNING,
                'outcome' => AuditLog::OUTCOME_FAILURE,
                'context' => [
                    'login_identifier' => $login,
                    'identifier_type' => filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'account_name',
                    'check_context' => $context,
                ],
            ]);

            Log::warning('Login identifier not found', [
                'login' => $login,
                'ip' => $ipAddress,
                'context' => $context,
            ]);

            // ログイン試行を記録（ロックアウト対策）
            \App\Models\MemberLoginAttempt::recordAttempt(
                $login,
                $ipAddress,
                request()->userAgent() ?? 'Unknown',
                false
            );

            // ロックアウト状態をチェック
            $lockoutInfo = \App\Helpers\LoginLockoutHelper::checkLockoutStatus(
                request(),
                $login,
                self::getLockoutSettings(\App\Models\SecuritySetting::class),
                \App\Models\SecuritySetting::class
            );

            // ロックアウト中の場合のみ例外を投げる（アクセス制限は維持）
            if ($lockoutInfo['is_ip_locked_out']) {
                $lockoutDuration = $lockoutInfo['settings']['lockout_duration'] ?? 30;
                throw ValidationException::withMessages([
                    'login' => __('auth.lockout', ['minutes' => $lockoutDuration]),
                ]);
            } elseif ($lockoutInfo['is_locked_out']) {
                throw ValidationException::withMessages([
                    'login' => __('auth.lockout', ['minutes' => $lockoutInfo['lockout_minutes']]),
                ]);
            }

            // 存在しない場合も存在する場合と同じレスポンス形式で返す（ユーザー列挙防止）
            return [
                'exists' => false,
                'has_passkey' => false,
                'user' => null,
            ];
        }
    }

    /**
     * パスキーが登録されているか確認
     *
     * @param  mixed  $user  ユーザーモデル
     */
    protected static function hasPasskey($user): bool
    {
        // webauthnCredentials リレーションが存在するか確認
        if (method_exists($user, 'webauthnCredentials')) {
            return $user->webauthnCredentials()->count() > 0;
        }

        // twoFaPasskeys リレーションが存在するか確認（管理者用）
        if (method_exists($user, 'twoFaPasskeys')) {
            return $user->twoFaPasskeys()->count() > 0;
        }

        // passkeys リレーションが存在するか確認（汎用）
        if (method_exists($user, 'passkeys')) {
            return $user->passkeys()->count() > 0;
        }

        return false;
    }

    /**
     * ロックアウト設定を取得
     *
     * @param  string  $settingModelClass  設定モデルクラス名
     */
    public static function getLockoutSettings(string $settingModelClass): array
    {
        return [
            'enabled' => (bool) $settingModelClass::getValue('login_attempt_limit_enabled', false),
            'max_attempts' => (int) $settingModelClass::getValue('login_attempt_max_attempts', 5),
            'max_attempts_ip' => (int) $settingModelClass::getValue('login_attempt_max_attempts_ip', null),
            'time_window' => (int) $settingModelClass::getValue('login_attempt_time_window', 15),
            'lockout_duration' => (int) $settingModelClass::getValue('login_attempt_lockout_duration', 30),
        ];
    }
}
