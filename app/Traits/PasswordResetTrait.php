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
 * 
 * 使用するコントローラーは以下の抽象メソッドを実装する必要があります：
 * - getSettingsGetter(): 設定取得用のクロージャを返す
 * - getPasswordResetBroker(): Password brokerの名前を返す
 * - getUserModelClass(): ユーザーモデルのクラス名を返す
 * - getForgotPasswordViewName(): パスワードリセットリンク要求画面のビュー名を返す
 * - getResetPasswordViewName(): パスワードリセット画面のビュー名を返す
 * - getPasswordResetRoute(): パスワードリセット処理のルート名を返す
 * - getLoginRoute(): ログイン画面のルート名を返す
 * - getCaptchaAction(): CAPTCHAアクション名を返す
 */
trait PasswordResetTrait
{
    /**
     * 設定取得用のクロージャを取得
     * 
     * @return callable function($key, $default)
     */
    abstract protected function getSettingsGetter(): callable;

    /**
     * Password brokerの名前を取得
     * 
     * @return string 'members' または 'users'
     */
    abstract protected function getPasswordResetBroker(): string;

    /**
     * ユーザーモデルのクラス名を取得
     * 
     * @return string
     */
    abstract protected function getUserModelClass(): string;

    /**
     * パスワードリセットリンク要求画面のビュー名を取得
     * 
     * @return string
     */
    abstract protected function getForgotPasswordViewName(): string;

    /**
     * パスワードリセット画面のビュー名を取得
     * 
     * @return string
     */
    abstract protected function getResetPasswordViewName(): string;

    /**
     * パスワードリセット処理のルート名を取得
     * 
     * @return string
     */
    abstract protected function getPasswordResetRoute(): string;

    /**
     * ログイン画面のルート名を取得
     * 
     * @return string
     */
    abstract protected function getLoginRoute(): string;

    /**
     * CAPTCHAアクション名を取得
     * 
     * @return string
     */
    abstract protected function getCaptchaAction(): string;

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

    /**
     * パスワードリセットリンク要求画面を表示（共通処理）
     * 
     * @return \Illuminate\View\View
     */
    protected function showForgotPasswordForm(): \Illuminate\View\View
    {
        $settingsGetter = $this->getSettingsGetter();
        $this->validatePasswordResetAvailability($settingsGetter);
        
        // CAPTCHA設定を取得
        $captchaEnabled = filter_var($settingsGetter('captcha_password_reset_enabled', '0'), FILTER_VALIDATE_BOOLEAN);

        // CAPTCHAウィジェットを生成
        $captchaWidget = null;
        if ($captchaEnabled) {
            $loginHelper = app(\App\Helpers\LoginHelper::class);
            $captchaWidget = $loginHelper->generateCaptchaWidget($this->getCaptchaAction());
        }
        
        return view($this->getForgotPasswordViewName(), array_merge(
            $this->getForgotPasswordViewData(),
            [
                'captchaEnabled' => $captchaEnabled,
                'captchaWidget' => $captchaWidget,
            ]
        ));
    }

    /**
     * パスワードリセットリンク要求画面用の追加データを取得
     * 
     * @return array
     */
    protected function getForgotPasswordViewData(): array
    {
        // デフォルトは空配列、各コントローラーでオーバーライド可能
        return [];
    }

    /**
     * パスワードリセットリンク送信処理（共通処理）
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function sendPasswordResetLink(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        $settingsGetter = $this->getSettingsGetter();
        $this->validatePasswordResetAvailability($settingsGetter);
        
        // CAPTCHA設定を取得
        $captchaEnabled = filter_var($settingsGetter('captcha_password_reset_enabled', '0'), FILTER_VALIDATE_BOOLEAN);

        // CAPTCHAを検証
        $captchaError = $this->validateCaptcha($request, $captchaEnabled);
        if ($captchaError) {
            return back()
                ->withInput(['email' => $captchaError['email']])
                ->withErrors($captchaError['errors']);
        }

        // バリデーションルールを取得
        $request->validate($this->getPasswordResetLinkValidationRules());

        // メールアドレスに対応するユーザーを確認
        $userModelClass = $this->getUserModelClass();
        $user = $userModelClass::where('email', $request->email)->first();
        
        // ユーザーが存在し、メール認証が未完了の場合はエラー
        if ($this->requiresEmailVerification($user)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('auth.email_not_verified')]);
        }

        // パスワードリセットリンクを送信
        $status = \Illuminate\Support\Facades\Password::broker($this->getPasswordResetBroker())->sendResetLink(
            $request->only('email')
        );

        return $status == \Illuminate\Support\Facades\Password::RESET_LINK_SENT
            ? back()->with('success', __($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }

    /**
     * パスワードリセット画面を表示（共通処理）
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    protected function showResetPasswordForm(\Illuminate\Http\Request $request): \Illuminate\View\View
    {
        $settingsGetter = $this->getSettingsGetter();
        $this->validatePasswordResetAvailability($settingsGetter);
        
        // パスワード設定を取得
        $passwordSettings = $this->getPasswordSettings($settingsGetter);
        
        return view($this->getResetPasswordViewName(), array_merge(
            $this->getResetPasswordViewData($request),
            [
                'passwordMinLength' => $passwordSettings['min_length'],
                'passwordRequireUppercase' => $passwordSettings['require_uppercase'],
                'passwordRequireNumber' => $passwordSettings['require_number'],
                'passwordRequireSymbol' => $passwordSettings['require_symbol'],
            ]
        ));
    }

    /**
     * パスワードリセット画面用の追加データを取得
     * 
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    protected function getResetPasswordViewData(\Illuminate\Http\Request $request): array
    {
        // デフォルトは空配列、各コントローラーでオーバーライド可能
        return [];
    }

    /**
     * パスワードリセット処理（共通処理）
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function resetPassword(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        $settingsGetter = $this->getSettingsGetter();
        
        // パスワード設定を取得してバリデーション
        $passwordSettings = $this->getPasswordSettings($settingsGetter);
        $request->validate($this->getPasswordResetValidationRules($passwordSettings));

        // パスワードリセットを実行
        $status = \Illuminate\Support\Facades\Password::broker($this->getPasswordResetBroker())->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $this->performPasswordReset($user, $request->password);
            }
        );

        // パスワードリセット成功時はログイン画面へリダイレクト
        return $status == \Illuminate\Support\Facades\Password::PASSWORD_RESET
            ? redirect()->route($this->getLoginRoute())->with('success', __($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }
}
