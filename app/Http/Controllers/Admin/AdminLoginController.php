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

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\AdminController;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use App\Http\Requests\Admin\AdminLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Member;
use App\Models\MemberSetting;
use App\Models\MembersTwoFactorDevice;
use Illuminate\Support\Facades\Hash;
use App\Services\AdminTwoFactorService;
use App\Services\AdminLoginNotificationService;
use App\Services\AdminLoginLockoutService;
use App\Services\MailServerValidatorService;
use App\Models\SecuritySetting;
use App\Captcha\CaptchaDriver;
use App\Helpers\CaptchaHelper;
use App\Helpers\TwoFactorHelper;
use App\Enums\TwoFactorMethod;
use App\Models\BaseSetting;
use App\Models\Member2faToken;
use App\Services\TwoFactorAttemptService;
use App\Services\PasskeyAuthenticationService;
use App\Services\RecoveryCodeService;
use App\Notifications\MemberVerificationCompletedNotification;
use App\Notifications\AdminMemberVerifiedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;



class AdminLoginController extends AdminController
{

    //初期設定を行う
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * Display the login view.
     */
    public function create()
    {
        $member = Auth::guard('member')->user();
        if ($member) {
            return redirect()->route('admin.dashboard');
        }

        // パスワードリセット機能の有効/無効設定を取得
        $passwordResetEnabled = (bool) MemberSetting::getValue('password_reset_enabled', true);
        
        // メールサーバーが設定・テスト済みの場合のみパスワードリセットを有効にする
        $this->viewParams['passwordResetEnabled'] = $passwordResetEnabled && MailServerValidatorService::canSendMail();

        // CAPTCHA設定を取得
        $captchaEnabled = CaptchaHelper::shouldShowCaptcha('admin_login');
        
        Log::info('AdminLoginController CAPTCHA debug', [
            'captchaEnabled' => $captchaEnabled,
            'siteKey' => CaptchaHelper::getSiteKey(),
            'driver' => CaptchaHelper::getDriver()
        ]);
        
        if ($captchaEnabled) {
            $this->viewParams['captchaEnabled'] = true;
            $this->viewParams['captchaDriver'] = CaptchaHelper::getDriver();
            
            // CAPTCHAウィジェットを生成
            $captchaDriverInstance = app(CaptchaDriver::class);
            $widget = $captchaDriverInstance->renderWidget(['action' => 'admin_login']);
            
            Log::info('AdminLoginController widget debug', [
                'widget_length' => strlen($widget),
                'widget_preview' => substr($widget, 0, 200) . '...'
            ]);
            
            $this->viewParams['captchaWidget'] = $widget;
        } else {
            $this->viewParams['captchaEnabled'] = false;
        }

        return view('admin::login', $this->viewParams);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(AdminLoginRequest $request)
    {
        $lockoutService = app(AdminLoginLockoutService::class);
        $login = $request->login;
        
        // 入力値がメールアドレスかアカウント名かを判定
        $isEmail = str_contains($login, '@');

        // CAPTCHA検証
        if (CaptchaHelper::shouldShowCaptcha('admin_login')) {
            Log::info('AdminLogin CAPTCHA verification start', [
                'login' => $login,
                'ip' => $request->ip(),
                'driver' => CaptchaHelper::getDriver(),
                'captcha_token_length' => strlen($request->input('g-recaptcha-response', ''))
            ]);
            
            $captchaDriverInstance = app(CaptchaDriver::class);
            $captchaResult = $captchaDriverInstance->verify($request);
            
            Log::info('AdminLogin CAPTCHA verification result', [
                'login' => $login,
                'ip' => $request->ip(),
                'is_valid' => $captchaResult->isValid(),
                'error_message' => $captchaResult->getErrorMessage(),
                'score' => $captchaResult->getScore(),
                'action' => $captchaResult->getAction(),
                'metadata' => $captchaResult->getMetadata()
            ]);
            
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

        // メールアドレスまたはアカウント名でメンバーを検索
        if ($isEmail) {
            // メールアドレスで検索（pending_emailも含む）
            $member = Member::where('email', $login)
                ->orWhere('pending_email', $login)
                ->first();
        } else {
            // アカウント名で検索
            $member = Member::where('account_name', $login)->first();
        }

        if (!$member || !Hash::check($request->password, $member->password)) {
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
        $email = $member->email;
        
        // 2FA 判定（有効な場合だけ進める）
        $twoFactor = app(AdminTwoFactorService::class);
        
        // メールサーバーのテストが完了していない場合は2FAをスキップ
        $mailServerTested = MailServerValidatorService::isMailServerTested();
        
        Log::info('[2FA Login] 二段階認証チェック', [
            'member_id' => $member->id,
            'email' => $member->email,
            'has_2fa' => $twoFactor->has($member),
            'mail_server_tested' => $mailServerTested,
            'two_factor_mode' => $member->two_factor_mode,
            'two_factor_method' => $member->two_factor_method,
        ]);
        
        if ($twoFactor->has($member) && $mailServerTested) {
            // 2FAロックアウトチェック
            $lockoutStatus = $twoFactor->checkLockout($member);
            
            if ($lockoutStatus['locked_out']) {
                Log::warning('[2FA Login] User is locked out from 2FA', [
                    'member_id' => $member->id,
                    'remaining_minutes' => $lockoutStatus['remaining_minutes'],
                ]);
                
                return back()->withErrors([
                    'email' => __('auth.2fa_locked_out', [
                        'minutes' => $lockoutStatus['remaining_minutes']
                    ]),
                ]);
            }

            session([
                'login.id' => $member->getAuthIdentifier(),
                'login.remember' => $request->boolean('remember'),
            ]);

            // 有効な認証方法を取得
            $effectiveMethod = $twoFactor->getEffectiveAuthMethod($member);
            
            Log::info('[2FA Login] 認証方法確認', [
                'member_id' => $member->id,
                'effective_method' => $effectiveMethod,
            ]);

            // メール認証の場合のみコード生成
            if ($effectiveMethod === TwoFactorMethod::EMAIL->value) {
                Log::info('[2FA Login] メール認証コード生成開始', [
                    'member_id' => $member->id,
                    'effective_method' => $effectiveMethod,
                ]);

                $twoFactor->generate($member); // ← ここでコード生成 + メール送信
                
                Log::info('[2FA Login] コード生成完了、リダイレクト');
            } else {
                Log::info('[2FA Login] Passkey認証へリダイレクト', [
                    'member_id' => $member->id,
                    'effective_method' => $effectiveMethod,
                ]);
            }

            // デフォルト認証方法に応じて適切なルートにリダイレクト
            $redirectRoute = $this->getTwoFactorMethodRoute($effectiveMethod);
            return redirect($redirectRoute);
        } else {
            // メールサーバー未テスト時はログに記録
            if ($twoFactor->has($member) && !$mailServerTested) {
                \Illuminate\Support\Facades\Log::info('Two-factor authentication skipped due to incomplete mail server tests', [
                    'member_id' => $member->id,
                    'email' => $member->email,
                ]);
            }
            // 成功したログインを記録（失敗記録をクリア）
            $lockoutService->handleSuccessfulLogin($email);

            // ログイン環境を記録、通知
            app(AdminLoginNotificationService::class)->handle($member, $request);

            // 2FA不要なら即ログイン
            Auth::guard('member')->login($member, $request->boolean('remember'));
            $request->session()->regenerate(true);
            
            // ログイン後にメール認証トークンをチェック
            $this->processEmailVerificationIfPending($member, $request);
            
            return redirect()->intended(route('admin.dashboard'));
        }
    }
    
    /**
     * ログイン後にメール認証が待機中の場合、認証処理を実行
     */
    protected function processEmailVerificationIfPending($member, $request)
    {
        $verificationData = session('email_verification_pending');
        
        if (!$verificationData) {
            return;
        }
        
        // トークンの有効期限チェック
        if ($verificationData['expires_at'] < now()->timestamp) {
            session()->forget('email_verification_pending');
            session()->flash('error', __('auth.verification_token_expired'));
            return;
        }
        
        // ログインしたメンバーと認証待ちのメンバーが一致するかチェック
        if ($member->id !== $verificationData['member_id']) {
            session()->forget('email_verification_pending');
            session()->flash('error', __('auth.verification_member_mismatch'));
            return;
        }
        
        // ハッシュを再検証
        $expectedHash = sha1($verificationData['email']);
        if (!hash_equals((string) $verificationData['hash'], $expectedHash)) {
            session()->forget('email_verification_pending');
            session()->flash('error', __('auth.verification_invalid'));
            return;
        }
        
        try {
            if ($verificationData['is_email_change']) {
                // メールアドレス変更の認証
                $member->email = $member->pending_email;
                $member->pending_email = null;
                $member->email_verified_at = now();
                $member->save();
                
                \Log::info('Email change verified after login', [
                    'member_id' => $member->id,
                    'new_email' => $member->email
                ]);
                
                session()->flash('success', __('admin/profile.email_verification_success'));
            } else {
                // 新規アカウントの認証
                $member->markEmailAsVerified();
                
                \Log::info('Account verified after login', [
                    'member_id' => $member->id,
                    'email' => $member->email
                ]);
                
                session()->flash('success', __('admin/profile.account_verification_success'));
                
                // メールサーバー設定済みの場合のみ通知を送信
                if (MailServerValidatorService::isMailServerTested()) {
                    try {
                        // メンバー本人に認証完了メールを送信
                        $member->notify(new MemberVerificationCompletedNotification());
                        
                        \Log::info('Verification completed notification sent to member', [
                            'member_id' => $member->id,
                            'email' => $member->email
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('Failed to send verification completed notification to member', [
                            'member_id' => $member->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                    
                    try {
                        // 管理者に通知
                        $adminEmail = BaseSetting::getValue('system_admin_email') 
                            ?? BaseSetting::getValue('notification_email');
                        
                        if ($adminEmail) {
                            Notification::route('mail', $adminEmail)
                                ->notify(new AdminMemberVerifiedNotification(
                                    $member,
                                    now()->format('Y-m-d H:i:s')
                                ));
                            
                            \Log::info('Verification notification sent to admin after login', [
                                'member_id' => $member->id,
                                'admin_email' => $adminEmail
                            ]);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Failed to send verification notification to admin', [
                            'member_id' => $member->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            // 認証完了後、セッションから削除
            session()->forget('email_verification_pending');
            
        } catch (\Exception $e) {
            \Log::error('Email verification failed after login', [
                'member_id' => $member->id,
                'error' => $e->getMessage()
            ]);
            session()->forget('email_verification_pending');
            session()->flash('error', __('auth.verification_failed'));
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('member')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return to_route('admin.login');
    }

    public function showTwoFactorForm()
    {
        if (!session()->has('login.id')) {
            return redirect()->route('admin.login');
        }

        $memberId = session('login.id');
        $member = Member::find($memberId);

        if (!$member) {
            return redirect()->route('admin.login');
        }

        // ロックアウトチェック
        $attemptService = app(TwoFactorAttemptService::class);
        if ($attemptService->isLockedOut($member)) {
            $remainingMinutes = $attemptService->getRemainingLockoutTime($member);
            return redirect()->route('admin.login')
                ->withErrors(['email' => __('two-factor.lockout.message', ['minutes' => $remainingMinutes])]);
        }

        // 既存の有効なコードがあるかチェック
        $hasValidToken = Member2faToken::where('member_id', $member->id)
            ->where('expires_at', '>', now())
            ->exists();

        // 有効なコードがない場合のみ新規生成
        if (!$hasValidToken) {
            Log::info('[Email Challenge] メール認証画面表示 - コード生成開始', [
                'member_id' => $member->id,
                'email' => $member->email,
            ]);
            
            $twoFactor = app(AdminTwoFactorService::class);
            $twoFactor->generate($member, 0); // 明示的にEMAIL認証を指定
            
            Log::info('[Email Challenge] コード生成完了');
        } else {
            Log::info('[Email Challenge] 既存の有効なコードを再利用', [
                'member_id' => $member->id,
            ]);
        }

        // TwoFactorHelperを使用して有効な認証方法を取得
        $twoFactorHelper = app(TwoFactorHelper::class);
        $enabledMethods = $twoFactorHelper->getEnabledTwoFactorMethods();
        $currentMethod = TwoFactorMethod::EMAIL->value;

        // Passkeyが有効かチェック
        $passkeyEnabled = MemberSetting::getValue('enabled_2fa_passkey', '0') === '1';
        
        // メンバーがPasskeyを登録しているかチェック
        $passkeyService = app(PasskeyAuthenticationService::class);
        $hasPasskey = $passkeyService->getCredentials($member)->isNotEmpty();

        // 有効な認証方法のリストを作成
        $availableMethods = [];
        foreach ($enabledMethods as $method) {
            if ($method !== $currentMethod) { // 現在の方法は除外
                // Passkeyの場合は、有効かつ登録済みの場合のみ表示
                if ($method === TwoFactorMethod::PASSKEY->value && (!$passkeyEnabled || !$hasPasskey)) {
                    continue;
                }
                
                $methodEnum = TwoFactorMethod::from($method);
                $availableMethods[] = [
                    'value' => $method,
                    'label' => $methodEnum->label(),
                    'url' => $this->getTwoFactorMethodRoute($method),
                ];
            }
        }

        // 二段階認証の設定値を取得（メンバー設定 > コンフィグ）
        $twoFactorExpireMinutes = (int) MemberSetting::getValue('two_factor_expire_minutes', config('two-factor.code_expiration', 5));
        $twoFactorResendIntervalSeconds = (int) MemberSetting::getValue('two_factor_resend_interval_seconds', config('two-factor.resend_interval', 60));

        return view('admin::two-factor.email-challenge', [
            'availableMethods' => $availableMethods,
            'currentMethod' => $currentMethod,
            'expireMinutes' => $twoFactorExpireMinutes,
            'resendIntervalSeconds' => $twoFactorResendIntervalSeconds,
        ]);
    }

    /**
     * 認証方法に応じたルートを取得
     */
    protected function getTwoFactorMethodRoute(int $method): string
    {
        return match($method) {
            TwoFactorMethod::EMAIL->value => route('admin.two-factor.email.show'),
            TwoFactorMethod::PASSKEY->value => route('admin.two-factor.passkey.show'),
            default => route('admin.two-factor.email.show'),
        };
    }

    public function verifyEmail(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $memberId = session('login.id');
        $member = Member::find($memberId);

        if (!$member) {
            return redirect()->route('admin.login');
        }

        // ロックアウトチェック
        $attemptService = app(TwoFactorAttemptService::class);
        if ($attemptService->isLockedOut($member)) {
            $remainingMinutes = $attemptService->getRemainingLockoutTime($member);
            session()->forget(['login.id', 'login.remember']);
            return redirect()->route('admin.login')
                ->withErrors(['email' => __('two-factor.lockout.message', ['minutes' => $remainingMinutes])]);
        }

        $twoFactor = app(AdminTwoFactorService::class);
        // メール認証コードを検証（明示的にEMAIL認証を指定）
        // 注: AdminTwoFactorService内で既に試行記録されるため、ここでは記録しない
        $isValid = $twoFactor->validate($member, $request->code, 0);
        
        if (!$isValid) {
            // 最大試行回数に達したかチェック
            if ($attemptService->hasReachedMaxAttempts($member)) {
                $lockoutDuration = (int) MemberSetting::getValue('2fa_lockout_duration', 30);
                session()->forget(['login.id', 'login.remember']);
                return redirect()->route('admin.login')
                    ->withErrors(['email' => __('two-factor.lockout.locked', ['minutes' => $lockoutDuration])]);
            }
            
            // 残り試行回数を取得
            $remainingAttempts = $attemptService->getRemainingAttempts($member);
            return back()->withErrors([
                'code' => __('two-factor.email.invalid_with_attempts', ['attempts' => $remainingAttempts])
            ]);
        }

        // 成功したログインを記録（失敗記録をクリア）
        app(AdminLoginLockoutService::class)->handleSuccessfulLogin($member->email);
        $attemptService->handleSuccess($member);

        // 最後に使用した二段階認証方法を更新
        $member->last_2fa_method = TwoFactorMethod::EMAIL->value;
        $member->save();

        // 回復コードが未生成の場合は自動生成
        $twoFactorHelper = app(TwoFactorHelper::class);
        if ($twoFactorHelper->hasNoRecoveryCodes($member)) {
            try {
                $codes = $twoFactorHelper->generateRecoveryCodes($member, true);
                // セッションに保存してダッシュボードで表示
                session(['auto_generated_recovery_codes' => $codes]);
                \Log::info("[Recovery Codes] 2FA初回クリア後に自動生成: ユーザーID {$member->id}");
            } catch (\Exception $e) {
                \Log::error("[Recovery Codes] 自動生成失敗: " . $e->getMessage());
            }
        }

        // ログイン環境を記録、通知
        app(AdminLoginNotificationService::class)->handle($member, $request);

        Auth::guard('member')->login($member, session('login.remember', false));
        session()->forget(['login.id', 'login.remember']);
        $request->session()->regenerate(true);
        
        // ログイン後にメール認証トークンをチェック
        $this->processEmailVerificationIfPending($member, $request);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function resendEmailCode(Request $request)
    {
        if (!session()->has('login.id')) {
            return response()->json([
                'success' => false,
                'message' => __('auth.failed')
            ], 401);
        }

        $member = Member::find(session('login.id'));

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => __('auth.failed')
            ], 401);
        }

        $twoFactor = app(AdminTwoFactorService::class);
        $twoFactor->generate($member, TwoFactorMethod::EMAIL->value);

        return response()->json([
            'success' => true,
            'message' => __('two-factor.email.resend_success')
        ]);
    }

    /**
     * 回復コード入力画面を表示
     */
    public function showRecoveryCodeForm()
    {
        if (!session()->has('login.id')) {
            return redirect()->route('admin.login');
        }

        $memberId = session('login.id');
        $member = Member::find($memberId);

        if (!$member) {
            return redirect()->route('admin.login');
        }

        // ロックアウトチェック
        $attemptService = app(TwoFactorAttemptService::class);
        if ($attemptService->isLockedOut($member)) {
            $remainingMinutes = $attemptService->getRemainingLockoutTime($member);
            return redirect()->route('admin.login')
                ->withErrors(['email' => __('two-factor.lockout.message', ['minutes' => $remainingMinutes])]);
        }

        return view('admin::two-factor.recovery-code-challenge');
    }

    /**
     * 回復コードを検証
     */
    public function confirmRecoveryCode(Request $request)
    {
        $request->validate([
            'recovery_code' => 'required|string',
        ]);

        $memberId = session('login.id');
        $member = Member::find($memberId);

        if (!$member) {
            return redirect()->route('admin.login');
        }

        // ロックアウトチェック
        $attemptService = app(TwoFactorAttemptService::class);
        if ($attemptService->isLockedOut($member)) {
            $remainingMinutes = $attemptService->getRemainingLockoutTime($member);
            session()->forget(['login.id', 'login.remember']);
            return redirect()->route('admin.login')
                ->withErrors(['email' => __('two-factor.lockout.message', ['minutes' => $remainingMinutes])]);
        }

        $recoveryCodeService = app(RecoveryCodeService::class);
        $isValid = $recoveryCodeService->validate($member, $request->recovery_code);

        // 試行を記録
        $attemptService->recordAttempt($member, 'recovery_code', $isValid);

        if (!$isValid) {
            // 最大試行回数に達したかチェック
            if ($attemptService->hasReachedMaxAttempts($member)) {
                $lockoutDuration = (int) MemberSetting::getValue('2fa_lockout_duration', 30);
                session()->forget(['login.id', 'login.remember']);
                return redirect()->route('admin.login')
                    ->withErrors(['email' => __('two-factor.lockout.locked', ['minutes' => $lockoutDuration])]);
            }

            // 残り試行回数を取得
            $remainingAttempts = $attemptService->getRemainingAttempts($member);
            return back()->withErrors([
                'recovery_code' => __('two-factor.recovery_code.invalid_with_attempts', ['attempts' => $remainingAttempts])
            ]);
        }

        // 成功したログインを記録（失敗記録をクリア）
        app(AdminLoginLockoutService::class)->handleSuccessfulLogin($member->email);
        $attemptService->handleSuccess($member);

        // ログイン環境を記録、通知
        app(AdminLoginNotificationService::class)->handle($member, $request);

        Auth::guard('member')->login($member, session('login.remember', false));
        session()->forget(['login.id', 'login.remember']);
        $request->session()->regenerate(true);

        // ログイン後にメール認証トークンをチェック
        $this->processEmailVerificationIfPending($member, $request);

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * メール認証フォームを表示
     */
    public function showEmailForm(Request $request)
    {
        if (!session()->has('login.id')) {
            return redirect()->route('admin.login');
        }

        $memberId = session('login.id');
        $member = Member::find($memberId);

        if (!$member) {
            return redirect()->route('admin.login');
        }

        // メール認証コードを生成・送信
        $twoFactor = app(AdminTwoFactorService::class);
        $twoFactor->generate($member, TwoFactorMethod::EMAIL->value);

        // 利用可能な認証方法を取得
        $twoFactorHelper = app(TwoFactorHelper::class);
        $enabledMethods = $twoFactorHelper->getEnabledTwoFactorMethods();
        $availableMethods = [];
        $currentMethod = TwoFactorMethod::EMAIL->value;

        foreach ($enabledMethods as $method) {
            if ($method !== $currentMethod) { // EMAIL以外
                $methodEnum = TwoFactorMethod::from($method);
                $availableMethods[] = [
                    'value' => $method,
                    'label' => $methodEnum->label(),
                    'url' => $this->getTwoFactorMethodRoute($method),
                ];
            }
        }

        // 二段階認証の設定値を取得（メンバー設定 > コンフィグ）
        $twoFactorExpireMinutes = (int) MemberSetting::getValue('two_factor_expire_minutes', config('two-factor.code_expiration', 5));
        $twoFactorResendIntervalSeconds = (int) MemberSetting::getValue('two_factor_resend_interval_seconds', config('two-factor.resend_interval', 60));

        return view('admin.two-factor.email-challenge', [
            'availableMethods' => $availableMethods,
            'currentMethod' => $currentMethod,
            'expireMinutes' => $twoFactorExpireMinutes,
            'resendIntervalSeconds' => $twoFactorResendIntervalSeconds,
        ]);
    }

    /**
     * Passkey認証フォームを表示
     */
    public function showPasskeyForm(Request $request)
    {
        if (!session()->has('login.id')) {
            return redirect()->route('admin.login');
        }

        $memberId = session('login.id');
        $member = Member::find($memberId);

        if (!$member) {
            return redirect()->route('admin.login');
        }

        // 利用可能な認証方法を取得
        $twoFactorHelper = app(TwoFactorHelper::class);
        $enabledMethods = $twoFactorHelper->getEnabledTwoFactorMethods();
        $availableMethods = [];
        $currentMethod = TwoFactorMethod::PASSKEY->value;

        foreach ($enabledMethods as $method) {
            if ($method !== $currentMethod) { // PASSKEY以外
                $methodEnum = TwoFactorMethod::from($method);
                $availableMethods[] = [
                    'value' => $method,
                    'label' => $methodEnum->label(),
                    'url' => $this->getTwoFactorMethodRoute($method),
                ];
            }
        }

        return view('admin.two-factor.passkey-challenge', [
            'availableMethods' => $availableMethods,
            'currentMethod' => $currentMethod,
        ]);
    }

    /**
     * Passkey認証チャレンジを取得
     */
    public function getPasskeyChallenge(Request $request)
    {
        if (!session()->has('login.id')) {
            return response()->json([
                'success' => false,
                'message' => __('auth.failed')
            ], 401);
        }

        $memberId = session('login.id');
        $member = Member::find($memberId);

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => __('auth.failed')
            ], 401);
        }

        try {
            $passkeyService = app(\App\Services\PasskeyAuthenticationService::class);

            // Passkey認証が利用可能かチェック
            if (!$passkeyService->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.passkey_https_required')
                ], 400);
            }

            // メンバーがPasskeyを登録しているかチェック
            $credentials = $passkeyService->getCredentials($member);
            if ($credentials->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => __('auth.passkey_not_registered')
                ], 400);
            }

            // 認証チャレンジを生成
            $options = $passkeyService->generateAuthenticationChallenge($member);

            Log::info('[Passkey Auth] チャレンジ生成成功', [
                'member_id' => $member->id,
                'credentials_count' => $credentials->count()
            ]);

            return response()->json([
                'success' => true,
                'challenge' => $options
            ]);
        } catch (\Exception $e) {
            Log::error('[Passkey Auth] チャレンジ生成エラー', [
                'member_id' => $member->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => __('auth.passkey_challenge_error')
            ], 500);
        }
    }

    /**
     * Passkey認証を検証
     */
    public function verifyPasskey(Request $request)
    {
        if (!session()->has('login.id')) {
            return response()->json([
                'success' => false,
                'message' => __('auth.failed')
            ], 401);
        }

        $memberId = session('login.id');
        $member = Member::find($memberId);

        if (!$member) {
            return response()->json([
                'success' => false,
                'message' => __('auth.failed')
            ], 401);
        }

        $request->validate([
            'response' => 'required|array',
            'response.id' => 'required|string',
            'response.response' => 'required|array',
        ]);

        try {
            $passkeyService = app(\App\Services\PasskeyAuthenticationService::class);
            $credentialData = $request->input('response');

            // 認証レスポンスを検証
            $isValid = $passkeyService->verifyAssertion($member, $credentialData);

            if ($isValid) {
                // 認証成功 - ログイン処理
                $lockoutService = app(AdminLoginLockoutService::class);
                $lockoutService->handleSuccessfulLogin($member->email);

                // 最後に使用した二段階認証方法を更新
                $member->last_2fa_method = TwoFactorMethod::PASSKEY->value;
                $member->save();

                // 回復コードが未生成の場合は自動生成
                $twoFactorHelper = app(TwoFactorHelper::class);
                if ($twoFactorHelper->hasNoRecoveryCodes($member)) {
                    try {
                        $codes = $twoFactorHelper->generateRecoveryCodes($member, true);
                        // セッションに保存してダッシュボードで表示
                        session(['auto_generated_recovery_codes' => $codes]);
                        \Log::info("[Recovery Codes] Passkey認証後に自動生成: ユーザーID {$member->id}");
                    } catch (\Exception $e) {
                        \Log::error("[Recovery Codes] Passkey認証後の自動生成失敗: " . $e->getMessage());
                    }
                }

                // ログイン環境を記録、通知
                app(AdminLoginNotificationService::class)->handle($member, $request);

                Auth::guard('member')->login($member, session('login.remember', false));
                $request->session()->regenerate();

                // セッションクリーンアップ
                session()->forget(['login.id', 'login.remember']);

                Log::info('[Passkey Auth] 認証成功', [
                    'member_id' => $member->id
                ]);

                return response()->json([
                    'success' => true,
                    'redirect' => route('admin.dashboard')
                ]);
            } else {
                Log::warning('[Passkey Auth] 認証失敗', [
                    'member_id' => $member->id
                ]);

                return response()->json([
                    'success' => false,
                    'message' => __('auth.passkey_verification_failed')
                ], 401);
            }
        } catch (\Exception $e) {
            Log::error('[Passkey Auth] 検証エラー', [
                'member_id' => $member->id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => __('auth.passkey_verification_error')
            ], 500);
        }
    }
}
