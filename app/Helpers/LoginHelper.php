<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class LoginHelper
{
    /**
     * ユーザーを認証（メールアドレスとパスワード）
     *
     * @param string $email メールアドレス
     * @param string $password パスワード
     * @param string $userModel ユーザーモデルクラス名
     * @return array ['success' => bool, 'user' => mixed|null, 'error' => string|null]
     */
    public function authenticateUser(string $email, string $password, string $userModel): array
    {
        // emailまたはpending_emailでユーザーを検索
        $user = $userModel::where('email', $email)
            ->orWhere('pending_email', $email)
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            Log::info('[Login] Authentication failed', [
                'email' => $email,
                'user_found' => (bool) $user,
            ]);

            return [
                'success' => false,
                'user' => null,
                'error' => 'invalid_credentials',
            ];
        }

        Log::info('[Login] Authentication successful', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return [
            'success' => true,
            'user' => $user,
            'error' => null,
        ];
    }

    /**
     * Two-FAが必要かチェック
     *
     * @param mixed $user ユーザーモデル
     * @param string $twoFactorServiceClass Two-FAサービスクラス名
     * @return array ['needs_two_fa' => bool, 'lockout_status' => array|null]
     */
    public function check2FARequired($user, string $twoFactorServiceClass): array
    {
        $twoFactorService = app($twoFactorServiceClass);
        
        // メールサーバーのテストが完了していない場合はTwo-FAをスキップ
        $mailServerTested = \App\Services\MailServerValidatorService::isMailServerTested();
        
        Log::info('[Login] Two-FA check', [
            'user_id' => $user->id,
            'has_two_fa' => $twoFactorService->has($user),
            'mail_server_tested' => $mailServerTested,
        ]);

        if (!$twoFactorService->has($user) || !$mailServerTested) {
            return [
                'needs_two_fa' => false,
                'lockout_status' => null,
            ];
        }

        // Two-FAロックアウトチェック
        $lockoutStatus = $twoFactorService->checkLockout($user);
        
        if ($lockoutStatus['locked_out']) {
            Log::warning('[Login] User is locked out from Two-FA', [
                'user_id' => $user->id,
                'remaining_minutes' => $lockoutStatus['remaining_minutes'],
            ]);
        }

        return [
            'needs_two_fa' => true,
            'lockout_status' => $lockoutStatus,
        ];
    }

    /**
     * Two-FAセッションを準備
     *
     * @param mixed $user ユーザーモデル
     * @param bool $remember Remember me
     * @return void
     */
    public function prepare2FASession($user, bool $remember): void
    {
        session([
            'login.id' => $user->getAuthIdentifier(),
            'login.remember' => $remember,
        ]);

        Log::info('[Login] Two-FA session prepared', [
            'user_id' => $user->id,
            'remember' => $remember,
        ]);
    }

    /**
     * ログインを完了（Two-FAなし）
     *
     * @param mixed $user ユーザーモデル
     * @param bool $remember Remember me
     * @param string $guard ガード名
     * @param Request $request リクエスト
     * @param string $lockoutServiceClass ロックアウトサービスクラス名
     * @param string|null $notificationServiceClass 通知サービスクラス名
     * @return void
     */
    public function completeLogin(
        $user,
        bool $remember,
        string $guard,
        Request $request,
        string $lockoutServiceClass,
        ?string $notificationServiceClass = null
    ): void {
        // 成功したログインを記録（失敗記録をクリア）
        app($lockoutServiceClass)->handleSuccessfulLogin($user->email);

        // ログイン環境を記録、通知
        if ($notificationServiceClass) {
            app($notificationServiceClass)->handle($user, $request);
        }

        // ログイン
        Auth::guard($guard)->login($user, $remember);
        $request->session()->regenerate();

        Log::info('[Login] Login completed', [
            'user_id' => $user->id,
            'guard' => $guard,
            'remember' => $remember,
        ]);
    }


    /**
     * ログイン失敗時のエラーメッセージを生成
     *
     * @param array $lockoutInfo ロックアウト情報
     * @param string $translationPrefix 翻訳キープレフィックス
     * @return string エラーメッセージ
     */
    public function getLoginFailedMessage(array $lockoutInfo, string $translationPrefix = 'auth'): string
    {
        if ($lockoutInfo['is_locked_out']) {
            return __("{$translationPrefix}.lockout", ['minutes' => $lockoutInfo['lockout_minutes']]);
        }
        
        if ($lockoutInfo['remaining_attempts'] > 0) {
            return __("{$translationPrefix}.failed_with_attempts", ['attempts' => $lockoutInfo['remaining_attempts']]);
        }

        return __("{$translationPrefix}.failed");
    }

    /**
     * 2FAロックアウトエラーメッセージを生成
     *
     * @param array $lockoutStatus ロックアウトステータス
     * @param string $translationPrefix 翻訳キープレフィックス
     * @return string エラーメッセージ
     */
    public function get2FALockoutMessage(array $lockoutStatus, string $translationPrefix = 'auth'): string
    {
        return __("{$translationPrefix}.two_fa_locked_out", [
            'minutes' => $lockoutStatus['remaining_minutes']
        ]);
    }

    /**
     * パスワードリセットが有効かチェック
     *
     * @param string $settingKey 設定キー
     * @return bool パスワードリセットが有効かどうか
     */
    public function isPasswordResetEnabled(string $settingKey = 'password_reset_enabled'): bool
    {
        $enabled = (bool) \App\Models\MemberSetting::getValue($settingKey, true);
        $mailServerReady = \App\Services\MailServerValidatorService::canSendMail();

        return $enabled && $mailServerReady;
    }

    /**
     * CAPTCHAが必要かチェック
     *
     * @param string $formKey フォームキー
     * @return bool CAPTCHAが必要かどうか
     */
    public function isCaptchaRequired(string $formKey): bool
    {
        return \App\Helpers\CaptchaHelper::shouldShowCaptcha($formKey);
    }

    /**
     * CAPTCHAウィジェットを生成
     *
     * @param string $action アクション名
     * @return string CAPTCHAウィジェットHTML
     */
    public function generateCaptchaWidget(string $action): string
    {
        $captchaDriver = app(\App\Captcha\CaptchaDriver::class);
        return $captchaDriver->renderWidget(['action' => $action]);
    }

    /**
     * CAPTCHAを検証
     *
     * @param Request $request リクエスト
     * @return array ['valid' => bool, 'error_message' => string|null]
     */
    public function verifyCaptcha(Request $request): array
    {
        $captchaDriver = app(\App\Captcha\CaptchaDriver::class);
        $result = $captchaDriver->verify($request);

        Log::info('[Login] CAPTCHA verification', [
            'is_valid' => $result->isValid(),
            'error_message' => $result->getErrorMessage(),
            'score' => $result->getScore(),
        ]);

        return [
            'valid' => $result->isValid(),
            'error_message' => $result->getErrorMessage(),
        ];
    }

    /**
     * セッションをクリーンアップ
     *
     * @param array $keys クリアするセッションキー
     * @return void
     */
    public function cleanupSession(array $keys = ['login.id', 'login.remember']): void
    {
        session()->forget($keys);
        
        Log::info('[Login] Session cleaned up', [
            'keys' => $keys,
        ]);
    }
}
