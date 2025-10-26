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
use App\Models\CaptchaFormSetting;
use App\Captcha\CaptchaDriver;
use App\Helpers\CaptchaHelper;
use Illuminate\Support\Facades\Log;



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
        $email = $request->email;

        // CAPTCHA検証
        if (CaptchaHelper::shouldShowCaptcha('admin_login')) {
            Log::info('AdminLogin CAPTCHA verification start', [
                'email' => $email,
                'ip' => $request->ip(),
                'driver' => CaptchaHelper::getDriver(),
                'captcha_token_length' => strlen($request->input('g-recaptcha-response', ''))
            ]);
            
            $captchaDriverInstance = app(CaptchaDriver::class);
            $captchaResult = $captchaDriverInstance->verify($request);
            
            Log::info('AdminLogin CAPTCHA verification result', [
                'email' => $email,
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
        if ($lockoutService->isLockedOut($email)) {
            $remainingMinutes = $lockoutService->getLockoutRemainingMinutes($email);
            return back()->withErrors([
                'email' => __('auth.lockout', ['minutes' => $remainingMinutes]),
            ]);
        }

        // IPアドレスベースのロックアウトもチェック
        if ($lockoutService->isIpLockedOut($request->ip())) {
            return back()->withErrors([
                'email' => __('auth.ip_lockout'),
            ]);
        }

        // emailまたはpending_emailでメンバーを検索（メールアドレス変更待ちの場合に対応）
        $member = Member::where('email', $email)
            ->orWhere('pending_email', $email)
            ->first();

        if (!$member || !Hash::check($request->password, $member->password)) {
            // 失敗したログインを記録
            $lockoutInfo = $lockoutService->handleFailedLogin($request, $email);
            
            $errorMessage = __('auth.failed');
            if ($lockoutInfo['is_locked_out']) {
                $errorMessage = __('auth.lockout', ['minutes' => $lockoutInfo['lockout_minutes']]);
            } elseif ($lockoutInfo['remaining_attempts'] > 0) {
                $errorMessage = __('auth.failed_with_attempts', ['attempts' => $lockoutInfo['remaining_attempts']]);
            }

            return back()->withErrors([
                'email' => $errorMessage,
            ]);
        }
        // 2FA 判定（有効な場合だけ進める）
        $twoFactor = app(AdminTwoFactorService::class);
        
        // メールサーバーのテストが完了していない場合は2FAをスキップ
        $mailServerTested = \App\Services\MailServerValidatorService::isMailServerTested();
        
        Log::info('[2FA Login] 二段階認証チェック', [
            'member_id' => $member->id,
            'email' => $member->email,
            'has_2fa' => $twoFactor->has($member),
            'mail_server_tested' => $mailServerTested,
            'two_factor_mode' => $member->two_factor_mode,
            'two_factor_method' => $member->two_factor_method,
        ]);
        
        if ($twoFactor->has($member) && $mailServerTested) {
            session([
                'login.id' => $member->getAuthIdentifier(),
                'login.remember' => $request->boolean('remember'),
            ]);

            // 有効な認証方法を取得
            $effectiveMethod = $twoFactor->getEffectiveAuthMethod($member);
            
            Log::info('[2FA Login] コード生成開始', [
                'member_id' => $member->id,
                'effective_method' => $effectiveMethod,
            ]);

            $twoFactor->generate($member); // ← ここでコード生成 + メール送信
            
            Log::info('[2FA Login] コード生成完了、リダイレクト');

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
                
                session()->flash('success', __('admin.profile.email_verification_success'));
            } else {
                // 新規アカウントの認証
                $member->markEmailAsVerified();
                
                \Log::info('Account verified after login', [
                    'member_id' => $member->id,
                    'email' => $member->email
                ]);
                
                session()->flash('success', __('admin.profile.account_verification_success'));
                
                // メールサーバー設定済みの場合のみ通知を送信
                if (\App\Services\MailServerValidatorService::isMailServerTested()) {
                    try {
                        // メンバー本人に認証完了メールを送信
                        $member->notify(new \App\Notifications\MemberVerificationCompletedNotification());
                        
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
                        $adminEmail = \App\Models\BaseSetting::getValue('system_admin_email') 
                            ?? \App\Models\BaseSetting::getValue('notification_email');
                        
                        if ($adminEmail) {
                            \Illuminate\Support\Facades\Notification::route('mail', $adminEmail)
                                ->notify(new \App\Notifications\AdminMemberVerifiedNotification(
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

        // TwoFactorHelperを使用して有効な認証方法を取得
        $twoFactorHelper = app(\App\Helpers\TwoFactorHelper::class);
        $enabledMethods = $twoFactorHelper->getEnabledTwoFactorMethods();
        $currentMethod = $twoFactorHelper->getEffectiveAuthMethod($member);

        // 認証方法の翻訳キーマッピング
        $methodLabels = [
            0 => __('common.two_factor_method.options.email'),
            1 => __('common.two_factor_method.options.device'),
            2 => __('common.two_factor_method.options.biometric'),
        ];

        // 有効な認証方法のリストを作成
        $availableMethods = [];
        foreach ($enabledMethods as $method) {
            if ($method !== $currentMethod) { // 現在の方法は除外
                $availableMethods[] = [
                    'value' => $method,
                    'label' => $methodLabels[$method] ?? '',
                    'url' => $this->getTwoFactorMethodRoute($method),
                ];
            }
        }

        // 二段階認証の設定値を取得
        $twoFactorExpireMinutes = (int) \App\Models\MemberSetting::getValue('two_factor_expire_minutes', 10);
        $twoFactorResendIntervalSeconds = (int) \App\Models\MemberSetting::getValue('two_factor_resend_interval_seconds', 60);

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
            0 => route('admin.two-factor.login'), // EMAIL
            1 => route('admin.two-factor.device.challenge'), // DEVICE
            2 => route('admin.two-factor.biometric.challenge'), // BIOMETRIC
            default => route('admin.two-factor.login'),
        };
    }

    public function confirmTwoFactor(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $memberId = session('login.id');
        $member = Member::find($memberId);

        if (!$member) {
            return redirect()->route('admin.login');
        }


        $twoFactor = app(AdminTwoFactorService::class);
        if (!$twoFactor->validate($member, $request->code)) {
            return back()->withErrors(['code' => __('two-factor.email.invalid')]);
        }

        // 成功したログインを記録（失敗記録をクリア）
        app(AdminLoginLockoutService::class)->handleSuccessfulLogin($member->email);

        // ログイン環境を記録、通知
        app(AdminLoginNotificationService::class)->handle($member, $request);

        Auth::guard('member')->login($member, session('login.remember', false));
        session()->forget(['login.id', 'login.remember']);
        $request->session()->regenerate(true);
        
        // ログイン後にメール認証トークンをチェック
        $this->processEmailVerificationIfPending($member, $request);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function resendTwoFactorCode(Request $request)
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
        $twoFactor->generate($member); // ← DB保存 & メール送信

        return response()->json([
            'success' => true,
            'message' => __('two-factor.email.resend_success')
        ]);
    }

    /**
     * デバイス認証画面を表示
     */
    public function showDeviceChallengeForm(Request $request)
    {
        if (!session()->has('login.id')) {
            return redirect()->route('admin.login');
        }

        $memberId = session('login.id');
        $member = Member::find($memberId);

        if (!$member) {
            return redirect()->route('admin.login');
        }

        // デバイス認証チャレンジを生成
        $deviceService = app(\App\Services\DeviceAuthenticationService::class);
        $challenge = $deviceService->generateDeviceChallenge($member);

        // TwoFactorHelperを使用して有効な認証方法を取得
        $twoFactorHelper = app(\App\Helpers\TwoFactorHelper::class);
        $enabledMethods = $twoFactorHelper->getEnabledTwoFactorMethods();
        $currentMethod = 1; // DEVICE

        // 認証方法の翻訳キーマッピング
        $methodLabels = [
            0 => __('common.two_factor_method.options.email'),
            1 => __('common.two_factor_method.options.device'),
            2 => __('common.two_factor_method.options.biometric'),
        ];

        // 有効な認証方法のリストを作成
        $availableMethods = [];
        foreach ($enabledMethods as $method) {
            if ($method !== $currentMethod) {
                $availableMethods[] = [
                    'value' => $method,
                    'label' => $methodLabels[$method] ?? '',
                    'url' => $this->getTwoFactorMethodRoute($method),
                ];
            }
        }

        // 二段階認証の設定値を取得
        $twoFactorExpireMinutes = (int) \App\Models\MemberSetting::getValue('two_factor_expire_minutes', 10);
        $twoFactorResendIntervalSeconds = (int) \App\Models\MemberSetting::getValue('two_factor_resend_interval_seconds', 60);

        return view('admin::two-factor.device-challenge', [
            'availableMethods' => $availableMethods,
            'currentMethod' => $currentMethod,
            'challenge' => $challenge,
            'expireMinutes' => $twoFactorExpireMinutes,
            'resendIntervalSeconds' => $twoFactorResendIntervalSeconds,
        ]);
    }

    /**
     * 生体認証画面を表示
     */
    public function showBiometricChallengeForm(Request $request)
    {
        if (!session()->has('login.id')) {
            return redirect()->route('admin.login');
        }

        $memberId = session('login.id');
        $member = Member::find($memberId);

        if (!$member) {
            return redirect()->route('admin.login');
        }

        // TwoFactorHelperを使用して有効な認証方法を取得
        $twoFactorHelper = app(\App\Helpers\TwoFactorHelper::class);
        $enabledMethods = $twoFactorHelper->getEnabledTwoFactorMethods();
        $currentMethod = 2; // BIOMETRIC

        // 認証方法の翻訳キーマッピング
        $methodLabels = [
            0 => __('common.two_factor_method.options.email'),
            1 => __('common.two_factor_method.options.device'),
            2 => __('common.two_factor_method.options.biometric'),
        ];

        // 有効な認証方法のリストを作成
        $availableMethods = [];
        foreach ($enabledMethods as $method) {
            if ($method !== $currentMethod) {
                $availableMethods[] = [
                    'value' => $method,
                    'label' => $methodLabels[$method] ?? '',
                    'url' => $this->getTwoFactorMethodRoute($method),
                ];
            }
        }

        return view('admin::two-factor.biometric-challenge', [
            'availableMethods' => $availableMethods,
            'currentMethod' => $currentMethod,
        ]);
    }

    /**
     * デバイス認証の承認状態をチェック（ポーリング用）
     */
    public function checkDeviceAuth(Request $request)
    {
        if (!session()->has('login.id')) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => __('auth.failed')
            ], 401);
        }

        $deviceService = app(\App\Services\DeviceAuthenticationService::class);
        $status = $deviceService->checkChallengeStatus();

        if (!$status) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'チャレンジが見つかりません'
            ]);
        }

        if ($status['status'] === 'approved') {
            $member = Member::find($status['member_id']);

            if ($member && $member->id == session('login.id')) {
                // 認証成功 - ログイン処理
                Auth::guard('member')->login($member);
                $request->session()->regenerate();

                // セッションクリーンアップ
                session()->forget(['login.id', 'login.remember', 'device_challenge_id']);

                return response()->json([
                    'success' => true,
                    'status' => 'approved',
                    'redirect' => route('admin.dashboard')
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'status' => $status['status']
        ]);
    }

    /**
     * デバイス認証メールを再送信
     */
    public function resendDeviceAuth(Request $request)
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
            // 既存のチャレンジを削除
            $challengeId = session('device_challenge_id');
            if ($challengeId) {
                MembersTwoFactorDevice::where('id', $challengeId)->delete();
            }

            // 新しいチャレンジを生成してメール送信
            $deviceService = app(\App\Services\DeviceAuthenticationService::class);
            $challenge = $deviceService->generateDeviceChallenge($member);

            Log::info("[Device Auth] 再送信成功: ユーザーID {$member->id}, チャレンジID {$challenge->id}");

            return response()->json([
                'success' => true,
                'message' => '認証メールを再送信しました'
            ]);
        } catch (\Exception $e) {
            Log::error("[Device Auth] 再送信エラー: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'メールの再送信に失敗しました'
            ], 500);
        }
    }

    /**
     * デバイス認証を承認（メールリンクから）
     */
    public function approveDeviceAuth(string $token)
    {
        $deviceService = app(\App\Services\DeviceAuthenticationService::class);

        if ($deviceService->approveChallenge($token)) {
            return view('admin::two-factor.device-approved');
        }

        return view('admin::two-factor.device-error', [
            'message' => 'このリンクは無効または期限切れです。'
        ]);
    }

    /**
     * デバイス認証を拒否（メールリンクから）
     */
    public function denyDeviceAuth(string $token)
    {
        $deviceService = app(\App\Services\DeviceAuthenticationService::class);

        if ($deviceService->denyChallenge($token)) {
            return view('admin::two-factor.device-denied');
        }

        return view('admin::two-factor.device-error', [
            'message' => 'このリンクは無効または期限切れです。'
        ]);
    }

    /**
     * 生体認証の確認
     */
    public function confirmBiometricAuth(Request $request)
    {
        // TODO: 生体認証の実装
        return response()->json([
            'success' => false,
            'message' => '生体認証は現在実装中です'
        ]);
    }
}
