<?php

namespace App\Traits;

use Illuminate\Http\Request;

/**
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
     * @return string ルート名（例: 'admin.login', 'users-plugin::mypage.login'）
     */
    abstract protected function getLoginRoute(): string;

    /**
     * ダッシュボードのルート名を取得（継承先で実装）
     * 
     * @return string ルート名（例: 'admin.dashboard', 'users-plugin::mypage.dashboard'）
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
     * @return string ルートプレフィックス（例: 'admin', 'users-plugin::mypage'）
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
     * pending_emailでのログインをサポートするかどうか（継承先で実装）
     * 
     * @return bool pending_emailログインのサポート
     */
    abstract protected function supportsPendingEmailLogin(): bool;

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
        
        // メールサーバーが設定・テスト済みの場合のみパスワードリセットを有効にする
        $viewParams['passwordResetEnabled'] = $passwordResetEnabled && \App\Services\MailServerValidatorService::canSendMail();

        // CAPTCHA設定を取得
        $captchaAction = $this->getCaptchaAction();
        $captchaEnabled = \App\Helpers\CaptchaHelper::shouldShowCaptcha($captchaAction);
        
        if ($captchaEnabled) {
            $viewParams['captchaEnabled'] = true;
            $viewParams['captchaDriver'] = \App\Helpers\CaptchaHelper::getDriver();
            
            // CAPTCHAウィジェットを生成
            $captchaDriverInstance = app(\App\Services\Captcha\CaptchaDriver::class);
            $widget = $captchaDriverInstance->renderWidget(['action' => $captchaAction]);
            
            $viewParams['captchaWidget'] = $widget;
        } else {
            $viewParams['captchaEnabled'] = false;
        }

        return view($this->getLoginViewName(), $viewParams);
    }

    /**
     * Handle an incoming authentication request.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(\Illuminate\Http\Request $request)
    {
        $lockoutService = app($this->getLockoutServiceClass());
        $login = $request->login;
        
        // 入力値がメールアドレスかアカウント名かを判定
        $isEmail = str_contains($login, '@');

        // CAPTCHA検証
        $captchaAction = $this->getCaptchaAction();
        if (\App\Helpers\CaptchaHelper::shouldShowCaptcha($captchaAction)) {
            $captchaDriverInstance = app(\App\Services\Captcha\CaptchaDriver::class);
            $captchaResult = $captchaDriverInstance->verify($request);
            
            if (!$captchaResult->isValid()) {
                return back()->withErrors([
                    'captcha' => $captchaResult->getErrorMessage(),
                ])->withInput($request->except('password'));
            }
        }

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

        if (!$user || !\Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
            // 失敗したログインを記録
            $lockoutInfo = $lockoutService->handleFailedLogin($request, $login);
            
            $errorMessage = __('auth.failed');
            if ($lockoutInfo['is_locked_out']) {
                $errorMessage = __('auth.lockout', ['minutes' => $lockoutInfo['lockout_minutes']]);
            } elseif ($lockoutInfo['remaining_attempts'] > 0) {
                $errorMessage = __('auth.failed_with_attempts', ['attempts' => $lockoutInfo['remaining_attempts']]);
            }

            return back()->withErrors([
                'login' => $errorMessage,
            ]);
        }
        
        // ロックアウト用にメールアドレスを取得
        $email = $user->email;
        
        // 2FA 判定
        $twoFactor = app(\App\Services\TwoFa\TwoFaService::class, [
            'settingModelClass' => $this->getSettingModelClass(),
            'context' => $this->getContext()
        ]);
        
        // メールサーバーのテストが完了していない場合は2FAをスキップ
        $mailServerTested = \App\Services\MailServerValidatorService::isMailServerTested();
        
        if ($twoFactor->has($user) && $mailServerTested) {
            // 2FAロックアウトチェック
            $lockoutStatus = $twoFactor->checkLockout($user);
            
            if ($lockoutStatus['locked_out']) {
                return back()->withErrors([
                    'email' => __('auth.two_fa_locked_out', [
                        'minutes' => $lockoutStatus['remaining_minutes']
                    ]),
                ]);
            }

            session([
                $this->getSessionPrefix() . '.id' => $user->getAuthIdentifier(),
                $this->getSessionPrefix() . '.remember' => $request->boolean('remember'),
            ]);

            // 有効な認証方法を取得
            $effectiveMethod = $twoFactor->getEffectiveAuthMethod($user);

            // メール認証の場合のみコード生成
            if ($effectiveMethod === \App\Enums\TwoFaMethod::EMAIL->value) {
                $twoFactor->generate($user);
                // セッションにメール送信済みフラグを設定（重複送信を防ぐ）
                $request->session()->put($this->getSessionPrefix() . '.email_sent', true);
            }

            // デフォルト認証方法に応じて適切なルートにリダイレクト
            $redirectRoute = \App\Helpers\TwoFaHelper::getTwoFaMethodRoute($this->getTwoFaRoutePrefix(), $effectiveMethod);
            
            return redirect()->route($redirectRoute);
        } else {
            // 成功したログインを記録（失敗記録をクリア）
            $lockoutService->handleSuccessfulLogin($email);

            // ログイン環境を記録、通知
            app($this->getLoginNotificationServiceClass())->handle($user, $request);

            // 2FA不要なら即ログイン
            \Illuminate\Support\Facades\Auth::guard($this->getGuardName())->login($user, $request->boolean('remember'));
            $request->session()->regenerate(true);
            
            // ログイン後にメール認証トークンをチェック
            $this->processEmailVerificationIfPending($user, $request);
            
            return redirect()->route($this->getDashboardRoute());
        }
    }

    /**
     * ログイン入力値からユーザーを検索
     * 
     * @param string $login ログイン入力値
     * @param bool $isEmail メールアドレスかどうか
     * @return mixed ユーザーモデルまたはnull
     */
    protected function findUserByLogin(string $login, bool $isEmail)
    {
        $userModelClass = $this->getUserModelClass();
        
        if ($isEmail) {
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
            return null;
        }
    }

    /**
     * Destroy an authenticated session.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
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
}
