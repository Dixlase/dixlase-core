<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2025 exc-D inc.
 * https://exc-d.com
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

namespace App\Traits;

use App\Services\PasswordValidationService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * パスワードリセットの共通ロジックを提供するTrait
 * 
 * このTraitは、メンバーとユーザーのパスワードリセット処理で共通する
 * ロジックを提供します。
 */
trait PasswordResetTrait
{
    /**
     * パスワード設定を取得
     *
     * @param callable $settingsGetter 設定取得用のクロージャ function($key, $default)
     * @return array パスワード設定の配列
     */
    protected function getPasswordSettings(callable $settingsGetter): array
    {
        return [
            'min_length' => (int) $settingsGetter('password_min_length', 8),
            'require_uppercase' => (bool) $settingsGetter('password_require_uppercase', false),
            'require_number' => (bool) $settingsGetter('password_require_number', false),
            'require_symbol' => (bool) $settingsGetter('password_require_symbol', false),
            'check_pwned' => (bool) $settingsGetter('password_check_pwned', false),
        ];
    }

    /**
     * パスワードリセット機能の有効性をチェック
     *
     * @param callable $settingsGetter 設定取得用のクロージャ
     * @return void
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    protected function validatePasswordResetAvailability(callable $settingsGetter): void
    {
        PasswordValidationService::abortIfPasswordResetUnavailable($settingsGetter);
    }

    /**
     * パスワードリセット用のバリデーションルールを取得
     *
     * @param array $passwordSettings getPasswordSettings()で取得した設定
     * @return array バリデーションルール
     */
    protected function getPasswordResetValidationRules(array $passwordSettings): array
    {
        return PasswordValidationService::getPasswordResetValidationRules(
            $passwordSettings['min_length'],
            $passwordSettings['require_uppercase'],
            $passwordSettings['require_number'],
            $passwordSettings['require_symbol'],
            $passwordSettings['check_pwned']
        );
    }

    /**
     * パスワードリセットリンク送信用のバリデーションルールを取得
     *
     * @return array バリデーションルール
     */
    protected function getPasswordResetLinkValidationRules(): array
    {
        return PasswordValidationService::getPasswordResetLinkValidationRules();
    }

    /**
     * CAPTCHAを検証（有効な場合のみ）
     *
     * @param Request $request リクエスト
     * @param bool $captchaEnabled CAPTCHA有効フラグ
     * @return array|null エラーがある場合は ['email' => string, 'errors' => array]、成功時はnull
     */
    protected function validateCaptcha(Request $request, bool $captchaEnabled): ?array
    {
        if (!$captchaEnabled) {
            return null;
        }

        $captchaDriver = app(\App\Captcha\CaptchaDriver::class);
        $result = $captchaDriver->verify($request);

        if (!$result->isValid()) {
            return [
                'email' => $request->input('email'),
                'errors' => ['captcha' => $result->getErrorMessage() ?? __('auth.captcha_failed')],
            ];
        }

        return null;
    }

    /**
     * パスワードリセット処理を実行
     *
     * @param object $user ユーザーまたはメンバーモデル
     * @param string $newPassword 新しいパスワード
     * @return void
     */
    protected function performPasswordReset(object $user, string $newPassword): void
    {
        $user->forceFill([
            'password' => Hash::make($newPassword),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));
    }

    /**
     * メール認証済みかチェック（必要に応じて）
     *
     * @param object|null $user ユーザーまたはメンバーモデル
     * @return bool メール認証が必要かつ未認証の場合true
     */
    protected function requiresEmailVerification(?object $user): bool
    {
        if (!$user) {
            return false;
        }

        // hasVerifiedEmailメソッドが存在し、かつ未認証の場合
        if (method_exists($user, 'hasVerifiedEmail') && !$user->hasVerifiedEmail()) {
            return true;
        }

        return false;
    }
}
