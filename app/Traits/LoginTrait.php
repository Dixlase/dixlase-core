<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 *
 * ログイン・認証処理の共通トレイト
 *
 * ログイン処理と二段階認証処理で共通して使用される設定メソッドと機能を提供します。
 * このトレイトを使用するコントローラーは、以下の抽象メソッドを実装する必要があります。
 */
trait LoginTrait
{
    /**
     * ログイン画面のルート名を取得（継承先で実装）
     *
     * @return string ルート名（例: 'admin.login', 'dixlase-users::mypage.login'）
     */
    abstract protected function getLoginRoute(): string;

    /**
     * ダッシュボードのルート名を取得（継承先で実装）
     *
     * @return string ルート名（例: 'admin.dashboard', 'dixlase-users::mypage.dashboard'）
     */
    abstract protected function getDashboardRoute(): string;

    /**
     * セッションキーのプレフィックスを取得（継承先で実装）
     *
     * @return string プレフィックス（例: 'login', 'two_fa'）
     */
    abstract protected function getSessionPrefix(): string;

    /**
     * ユーザーモデルクラス名を取得（継承先で実装）
     *
     * @return string モデルクラス名（例: 'App\Models\Member', 'Plugins\DixlaseUsers\App\Models\DixlaseUsersUser'）
     */
    abstract protected function getUserModelClass(): string;

    /**
     * 認証ガード名を取得（継承先で実装）
     *
     * @return string ガード名（例: 'member', 'user'）
     */
    abstract protected function getGuardName(): string;

    /**
     * コンテキストを取得（継承先で実装）
     *
     * @return string コンテキスト（'admin' または 'user'）
     */
    abstract protected function getContext(): string;

    /**
     * 二段階認証ルートのプレフィックスを取得
     *
     * @return string ルートプレフィックス（例: 'admin', 'dixlase-users::mypage'）
     */
    abstract protected function getTwoFaRoutePrefix(): string;

    /**
     * ログアウト後のリダイレクト先を取得（継承先で実装）
     *
     * @return string リダイレクト先のルート名またはURL
     */
    abstract protected function getLogoutRedirectRoute(): string;

    /**
     * 設定モデルクラス名を取得（継承先で実装）
     *
     * @return string 設定モデルクラス名
     */
    abstract protected function getSettingModelClass(): string;

    /**
     * ロックアウトサービスクラス名を取得（継承先で実装）
     *
     * @return string ロックアウトサービスクラス名
     */
    abstract protected function getLockoutServiceClass(): string;

    /**
     * ログイン通知サービスクラス名を取得（継承先で実装）
     *
     * @return string ログイン通知サービスクラス名
     */
    abstract protected function getLoginNotificationServiceClass(): string;

    /**
     * ログインビュー名を取得（継承先で実装）
     *
     * @return string ログインビュー名
     */
    abstract protected function getLoginViewName(): string;

    /**
     * CAPTCHAアクション名を取得（継承先で実装）
     *
     * @return string CAPTCHAアクション名
     */
    abstract protected function getCaptchaAction(): string;

    /**
     * パスワードリセット機能が有効かどうかを取得（継承先で実装）
     *
     * @return bool パスワードリセット機能の有効/無効
     */
    abstract protected function isPasswordResetEnabled(): bool;

    /**
     * アカウント名でのログインをサポートするかどうか（継承先で実装）
     *
     * @return bool アカウント名ログインのサポート
     */
    abstract protected function supportsAccountNameLogin(): bool;

    /**
     * メールアドレスでのログインをサポートするかどうか（継承先で実装）
     *
     * @return bool メールアドレスログインのサポート
     */
    abstract protected function supportsEmailLogin(): bool;

    /**
     * pending_emailでのログインをサポートするかどうか（継承先で実装）
     *
     * @return bool pending_emailログインのサポート
     */
    abstract protected function supportsPendingEmailLogin(): bool;

    /**
     * リカバリーコード画面のルート名を取得（継承先で実装）
     *
     * @return string ルート名（例: 'admin.two-fa.recovery-code.show', 'dixlase-users::mypage.two-fa.recovery-code.show'）
     */
    abstract protected function getRecoveryCodeRoute(): string;

    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function create()
    {
        $user = \Illuminate\Support\Facades\Auth::guard($this->getGuardName())->user();
        if ($user) {
            return redirect()->route($this->getDashboardRoute());
        }

        $viewParams = [];

        // パスワードリセット機能の有効/無効設定を取得
        $passwordResetEnabled = $this->isPasswordResetEnabled();
        $canSendMail = \App\Services\MailServerValidatorService::canSendMail();

        // メールサーバーが設定・テスト済みの場合のみパスワードリセットを有効にする
        $viewParams['canResetPassword'] = $passwordResetEnabled && $canSendMail;

        // CAPTCHA設定を取得
        $captchaAction = $this->getCaptchaAction();
        $captchaEnabled = \App\Helpers\CaptchaHelper::shouldShowCaptcha($captchaAction);
        $viewParams['captchaEnabled'] = $captchaEnabled;
        $viewParams['captchaDriver'] = \App\Helpers\CaptchaHelper::getDriver();
        $viewParams['captchaWidget'] = \App\Helpers\CaptchaHelper::renderWidget($captchaAction);

        // パスキー認証ボタンの表示判定
        // ステップ1では常に表示（識別子チェック後にhasPasskeyで制御）
        // ステップ2では二段階認証とパスキーの設定に基づいて表示
        $settingModelClass = $this->getSettingModelClass();
        $viewParams['passkeyEnabled'] = $this->shouldShowPasskeyButton($settingModelClass);

        // 戻るリンクの URL（welcome ルート未定義時は / にフォールバック）
        $viewParams['backUrl'] = \Illuminate\Support\Facades\Route::has('welcome') ? route('welcome') : url('/');

        return view($this->getLoginViewName(), $viewParams);
    }

    /**
     * Handle an incoming authentication request.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(\Illuminate\Http\Request $request)
    {
        $lockoutService = app($this->getLockoutServiceClass());
        $login = $request->login;

        // 入力値がメールアドレスかアカウント名かを判定
        $isEmail = str_contains($login, '@');

        // CAPTCHA検証（識別子チェックで検証済みの場合はスキップ）
        $captchaVerifiedKey = 'captcha_verified_'.$login;
        $captchaVerifiedTime = session()->get($captchaVerifiedKey);
        $captchaVerified = $captchaVerifiedTime && (time() - $captchaVerifiedTime) < 300; // 5分以内

        if (! $captchaVerified) {
            $captchaAction = $this->getCaptchaAction();
            $captchaResult = \App\Helpers\CaptchaHelper::verify($request, $captchaAction);

            if ($captchaResult && ! $captchaResult->isValid()) {
                return back()->withErrors([
                    'captcha' => $captchaResult->getErrorMessage(),
                ])->withInput($request->except('password'));
            }
        }

        // CAPTCHA検証済みフラグをクリア
        session()->forget($captchaVerifiedKey);

        // ロックアウト状態をチェック
        if ($lockoutService->isLockedOut($login)) {
            $remainingMinutes = $lockoutService->getLockoutRemainingMinutes($login);

            return back()->withErrors([
                'login' => __('auth.lockout', ['minutes' => $remainingMinutes]),
            ]);
        }

        // IPアドレスベースのロックアウトもチェック
        if ($lockoutService->isIpLockedOut($request->ip())) {
            return back()->withErrors([
                'login' => __('auth.ip_lockout'),
            ]);
        }

        // ユーザーを検索
        $user = $this->findUserByLogin($login, $isEmail);

        // ユーザーが見つからない、またはパスワードが間違っている場合
        if (! $user || ! \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            // 失敗したログインを記録
            $failureReason = \App\Models\MemberLoginAttempt::FAILURE_INVALID_PASSWORD;
            $lockoutInfo = $lockoutService->handleFailedLogin($request, $login, $failureReason);

            $errorMessage = __('auth.failed');

            // IPベースのロックアウトをチェック
            if ($lockoutInfo['is_ip_locked_out']) {
                $lockoutDuration = $lockoutInfo['settings']['lockout_duration'] ?? 30;
                $errorMessage = __('auth.lockout', ['minutes' => $lockoutDuration]);
            } elseif ($lockoutInfo['is_locked_out']) {
                $errorMessage = __('auth.lockout', ['minutes' => $lockoutInfo['lockout_minutes']]);
            } elseif ($lockoutInfo['remaining_attempts'] > 0) {
                $errorMessage = __('auth.failed_with_attempts', ['attempts' => $lockoutInfo['remaining_attempts']]);
            }

            // エラーメッセージをセッションフラッシュメッセージとして保存
            return back()
                ->with('error', $errorMessage)
                ->withInput($request->except('password'));
        }

        // ロックアウト用にメールアドレスを取得
        $email = $user->email;

        // 2FA 判定
        $twoFactor = app(\App\Services\TwoFa\TwoFaService::class, [
            'settingModelClass' => $this->getSettingModelClass(),
            'context' => $this->getContext(),
        ]);

        // メールサーバーのテストが完了していない場合は2FAをスキップ
        $mailServerTested = \App\Services\MailServerValidatorService::isMailServerTested();

        if ($twoFactor->has($user) && $mailServerTested) {
            // 2FAロックアウトチェック
            $lockoutStatus = $twoFactor->checkLockout($user);

            if ($lockoutStatus['locked_out']) {
                return back()->withErrors([
                    'email' => __('auth.two_fa_locked_out', [
                        'minutes' => $lockoutStatus['remaining_minutes'],
                    ]),
                ]);
            }

            session([
                $this->getSessionPrefix().'.id' => $user->getAuthIdentifier(),
                $this->getSessionPrefix().'.remember' => $request->boolean('remember'),
                $this->getSessionPrefix().'.auth_method' => 'password', // パスワード認証を記録
            ]);

            // 有効な認証方法を取得
            $effectiveMethod = $twoFactor->getEffectiveAuthMethod($user);

            // メール認証の場合のみコード生成
            if ($effectiveMethod === \App\Enums\TwoFaMethod::EMAIL->value) {
                $twoFactor->generate($user);
                // セッションにメール送信済みフラグを設定（重複送信を防ぐ）
                $request->session()->put($this->getSessionPrefix().'.email_sent', true);
            }

            // デフォルト認証方法に応じて適切なルートにリダイレクト
            $redirectRoute = \App\Helpers\TwoFaHelper::getTwoFaMethodRoute($this->getTwoFaRoutePrefix(), $effectiveMethod);

            return redirect()->route($redirectRoute);
        } else {
            // 成功したログインを記録（失敗記録をクリア）
            $lockoutService->handleSuccessfulLogin($email, $request);

            // ログイン環境を記録、通知
            app($this->getLoginNotificationServiceClass())->handle($user, $request);

            // 2FA不要なら即ログイン
            $guardName = $this->getGuardName();
            $remember = $request->boolean('remember');

            Auth::guard($guardName)->login($user, $remember);
            $request->session()->regenerate();

            // ログイン後にメール認証トークンをチェック
            $this->processEmailVerificationIfPending($user, $request);

            return redirect()->route($this->getDashboardRoute());
        }
    }

    /**
     * ログイン入力値からユーザーを検索
     *
     * @param  string  $login  ログイン入力値
     * @param  bool  $isEmail  メールアドレスかどうか
     * @return mixed ユーザーモデルまたはnull
     */
    protected function findUserByLogin(string $login, bool $isEmail)
    {
        $userModelClass = $this->getUserModelClass();

        if ($isEmail) {
            // メールアドレスログインが無効な場合はnull
            if (! $this->supportsEmailLogin()) {
                return;
            }

            // メールアドレスで検索
            $query = $userModelClass::where('email', $login);

            // pending_emailもサポートする場合
            if ($this->supportsPendingEmailLogin()) {
                $query->orWhere('pending_email', $login);
            }

            return $query->first();
        } else {
            // アカウント名でのログインをサポートする場合
            if ($this->supportsAccountNameLogin()) {
                return $userModelClass::where('account_name', $login)->first();
            }

            // アカウント名ログインをサポートしない場合はnull
            return;
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(\Illuminate\Http\Request $request): \Illuminate\Http\RedirectResponse
    {
        \Illuminate\Support\Facades\Auth::guard($this->getGuardName())->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $redirectRoute = $this->getLogoutRedirectRoute();

        // ルート名かURLかを判定
        if (str_starts_with($redirectRoute, '/') || str_starts_with($redirectRoute, 'http')) {
            return redirect($redirectRoute);
        }

        return to_route($redirectRoute);
    }

    /**
     * パスキーボタンを表示するかチェック
     *
     * 優先順位:
     * 1. メールサーバー未設定の場合は非表示
     * 2. 全体設定で二段階認証またはパスキーが無効の場合は非表示
     * 3. それ以外はステップ1で表示（識別子チェック後にhasPasskeyで制御）
     */
    protected function shouldShowPasskeyButton(string $settingModelClass): bool
    {
        // メールサーバー設定チェック（最優先）
        $twoFaHelper = app(\App\Helpers\TwoFaHelper::class);
        if (! $twoFaHelper->isMailConfigured()) {
            return false;
        }

        // パスキーモードを取得（0=無効、1=有効、2=プロフィールに従う）
        $twoFaPasskeyMode = (int) $settingModelClass::getValue('two_fa_passkey_mode', '2');

        // パスキーが無効の場合は非表示
        if ($twoFaPasskeyMode === 0) {
            return false;
        }

        // 二段階認証モードを取得（0=無効、1=異なるデバイス・IP、2=常に有効、3=プロフィール設定に従う）
        $twoFaMode = (int) $settingModelClass::getValue('two_fa_mode', '0');

        // 二段階認証が無効の場合は非表示
        if ($twoFaMode === 0) {
            return false;
        }

        // 二段階認証とパスキーが有効な場合は表示
        // ステップ1で表示し、識別子チェック後にhasPasskeyで制御
        return true;
    }
}
