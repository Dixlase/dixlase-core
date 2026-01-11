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
use App\Models\MembersTwoFaDevice;
use Illuminate\Support\Facades\Hash;
use App\Services\TwoFa\TwoFaService;
use App\Services\AdminLoginNotificationService;
use App\Services\AdminLoginLockoutService;
use App\Services\MailServerValidatorService;
use App\Models\SecuritySetting;
use App\Captcha\CaptchaDriver;
use App\Helpers\CaptchaHelper;
use App\Helpers\TwoFaHelper;
use App\Enums\TwoFaMethod;
use App\Models\BaseSetting;
use App\Models\MemberTwoFaToken;
use App\Services\TwoFa\TwoFaAttemptService;
use App\Services\TwoFa\TwoFaPasskeyService;
use App\Services\TwoFa\TwoFaRecoveryCodeService;
use App\Notifications\MemberVerificationCompletedNotification;
use App\Notifications\AdminMemberVerifiedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Traits\TwoFa\TwoFaAuthenticationTrait;
use App\Repositories\BaseSettingRepository;



class AdminLoginController extends AdminController
{
    use TwoFaAuthenticationTrait;

    protected $baseSettingRepository;

    //初期設定を行う
    public function __construct(BaseSettingRepository $baseSettingRepository)
    {
        parent::__construct();
        $this->baseSettingRepository = $baseSettingRepository;
    }
    
    /**
     * 設定モデルクラス名を取得
     */
    protected function getSettingModelClass(): string
    {
        return MemberSetting::class;
    }

    /**
     * ログインルート名を取得
     */
    protected function getLoginRoute(): string
    {
        return 'admin.login';
    }

    /**
     * ダッシュボードのルート名を取得
     */
    protected function getDashboardRoute(): string
    {
        return 'admin.dashboard';
    }

    /**
     * セッションキーのプレフィックスを取得
     */
    protected function getSessionPrefix(): string
    {
        return 'login';
    }

    /**
     * 二段階認証サービスのインスタンスを取得
     */
    protected function getTwoFaService()
    {
        return app(TwoFaService::class, [
            'settingModelClass' => MemberSetting::class,
            'context' => 'admin'
        ]);
    }

    /**
     * ユーザーモデルクラス名を取得
     */
    protected function getUserModelClass(): string
    {
        return Member::class;
    }

    /**
     * 認証ガード名を取得
     */
    protected function getGuardName(): string
    {
        return 'web';
    }

    /**
     * コンテキストを取得
     */
    protected function getContext(): string
    {
        return 'admin';
    }

    /**
     * 二段階認証ルートのプレフィックスを取得
     */
    protected function getTwoFaRoutePrefix(): string
    {
        return $this->baseSettingRepository->get('admin_url', 'admin');
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
        $twoFactor = app(TwoFaService::class, [
            'settingModelClass' => MemberSetting::class,
            'context' => 'admin'
        ]);
        
        // メールサーバーのテストが完了していない場合は2FAをスキップ
        $mailServerTested = MailServerValidatorService::isMailServerTested();
        
        Log::info('[2FA Login] 二段階認証チェック', [
            'member_id' => $member->id,
            'email' => $member->email,
            'has_two_fa' => $twoFactor->has($member),
            'mail_server_tested' => $mailServerTested,
            'two_fa_mode' => $member->two_fa_mode,
            'default_two_fa_method' => $member->default_two_fa_method,
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
                    'email' => __('auth.two_fa_locked_out', [
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
            if ($effectiveMethod === TwoFaMethod::EMAIL->value) {
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
            $redirectRoute = $this->getTwoFaMethodRoute($effectiveMethod);
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

}
