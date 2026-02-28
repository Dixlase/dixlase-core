<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * @api プラグイン/テーマから使用可能な安定APIです
 *
 * パスキーログインの共通トレイト
 *
 * WebAuthnを使用したパスキー認証によるログイン処理
 */
trait PasskeyLoginTrait
{
    protected $passkeyService;

    /**
     * ユーザーモデルクラス名を取得（継承先で実装）
     *
     * @return string ユーザーモデルクラス名
     */
    abstract protected function getUserModelClass(): string;

    /**
     * 設定モデルクラス名を取得（継承先で実装）
     *
     * @return string 設定モデルクラス名
     */
    abstract protected function getSettingModelClass(): string;

    /**
     * 認証ガード名を取得（継承先で実装）
     *
     * @return string ガード名（例: 'member', 'user'）
     */
    abstract protected function getGuardName(): string;

    /**
     * ダッシュボードのルート名を取得（継承先で実装）
     *
     * @return string ルート名
     */
    abstract protected function getDashboardRoute(): string;

    /**
     * セッションキーのプレフィックスを取得（継承先で実装）
     *
     * @return string プレフィックス（例: 'login', 'user_login'）
     */
    abstract protected function getSessionPrefix(): string;

    /**
     * ログイン通知サービスクラス名を取得（継承先で実装）
     *
     * @return string ログイン通知サービスクラス名
     */
    abstract protected function getLoginNotificationServiceClass(): string;

    /**
     * 翻訳プレフィックスを取得（継承先で実装）
     *
     * @return string 翻訳プレフィックス（例: 'auth', 'dixlase-users::auth'）
     */
    abstract protected function getTranslationPrefix(): string;

    /**
     * パスキー認証のチャレンジを取得
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getChallenge(Request $request)
    {
        $request->validate([
            'login' => 'required|string|max:255',
        ]);

        $login = $request->input('login');
        $userModelClass = $this->getUserModelClass();

        // ユーザー検索
        $user = $this->findUserByLogin($login, $userModelClass);

        if (! $user) {
            $errorMessage = __($this->getTranslationPrefix().'.failed');

            return response()->json([
                'success' => false,
                'error' => $errorMessage,
            ], 422);
        }

        // パスキーが登録されているか確認
        if (! $user->webauthnCredentials()->exists()) {
            $errorMessage = __($this->getTranslationPrefix().'.no_passkey_registered');

            return response()->json([
                'success' => false,
                'error' => $errorMessage,
            ], 422);
        }

        // 二段階認証が有効かチェック
        $settingModelClass = $this->getSettingModelClass();
        $globalTwoFaMode = $settingModelClass::getValue('two_fa_mode', 0);

        // グローバル設定が無効（0）の場合のみ、個別設定をチェック
        if ($globalTwoFaMode == 0) {
            if ($user->getTwoFaMode() === 0) {
                $errorMessage = __($this->getTranslationPrefix().'.two_fa_disabled');

                return response()->json([
                    'success' => false,
                    'error' => $errorMessage,
                ], 422);
            }
        }

        try {
            // WebAuthnチャレンジを生成
            $challengeData = $this->passkeyService->generateLoginChallenge($user);

            // セッションにユーザーIDとチャレンジIDを保存
            session([
                'passkey_login_'.$this->getSessionPrefix().'_id' => $user->id,
                'passkey_challenge_id' => $challengeData['id'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'challenge' => $challengeData['publicKey'],
            ]);
        } catch (\Exception $e) {
            Log::error('Passkey challenge generation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => __('common.error_occurred'),
            ], 500);
        }
    }

    /**
     * パスキー認証を検証してログイン
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request)
    {
        $sessionKey = 'passkey_login_'.$this->getSessionPrefix().'_id';
        $userId = session($sessionKey);

        if (! $userId) {
            return response()->json([
                'error' => __($this->getTranslationPrefix().'.failed'),
            ], 422);
        }

        $userModelClass = $this->getUserModelClass();
        $user = $userModelClass::find($userId);

        if (! $user) {
            session()->forget($sessionKey);

            return response()->json([
                'error' => __($this->getTranslationPrefix().'.failed'),
            ], 422);
        }

        try {
            // WebAuthn認証を検証
            $challengeId = session('passkey_challenge_id');
            $verified = $this->passkeyService->verifyLoginChallenge($user, $request->all(), $challengeId);

            if (! $verified) {
                return response()->json([
                    'error' => __($this->getTranslationPrefix().'.failed'),
                ], 422);
            }

            // ログイン成功
            $guardName = $this->getGuardName();
            Auth::guard($guardName)->login($user, true);
            session()->forget([$sessionKey, 'passkey_challenge_id']);

            // パスキー認証を記録
            session([$this->getSessionPrefix().'.auth_method' => 'passkey']);

            // ファイルログに記録
            Log::info('Passkey login successful', [
                'user_id' => $user->id,
                'guard' => $guardName,
                'ip' => $request->ip(),
            ]);

            // ログイン通知
            if ($user->login_notification_mode !== 0) {
                try {
                    $notificationServiceClass = $this->getLoginNotificationServiceClass();
                    $notificationService = app($notificationServiceClass);
                    $notificationService->handle($user, $request);
                } catch (\Exception $e) {
                    Log::warning('Login notification failed', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'redirect' => route($this->getDashboardRoute()),
            ]);
        } catch (\Exception $e) {
            Log::error('Passkey verification failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => __($this->getTranslationPrefix().'.failed'),
            ], 422);
        }
    }

    /**
     * ログイン入力値からユーザーを検索
     *
     * @param  string  $login  ログイン入力値
     * @param  string  $userModelClass  ユーザーモデルクラス名
     * @return mixed ユーザーモデルまたはnull
     */
    protected function findUserByLogin(string $login, string $userModelClass)
    {
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL) !== false;

        // アカウント名カラムが存在するか確認
        $hasAccountNameColumn = false;
        if (method_exists($userModelClass, 'getTable')) {
            $instance = new $userModelClass();
            $hasAccountNameColumn = \Illuminate\Support\Facades\Schema::hasColumn($instance->getTable(), 'account_name');
        }

        // アカウント名カラムがある場合は LoginIdentifierMode を考慮
        if ($hasAccountNameColumn) {
            $mode = \App\Enums\LoginIdentifierMode::tryFrom(
                (int) \App\Models\SecuritySetting::getValue('login_identifier_mode', \App\Enums\LoginIdentifierMode::EmailOrAccountName->value)
            ) ?? \App\Enums\LoginIdentifierMode::EmailOrAccountName;

            if ($isEmail && $mode->supportsEmail()) {
                return $userModelClass::where('email', $login)->first();
            } elseif (! $isEmail && $mode->supportsAccountName()) {
                return $userModelClass::where('account_name', $login)->first();
            }

            return;
        }

        // アカウント名カラムがない場合はメールアドレスのみ
        return $userModelClass::where('email', $login)->first();
    }
}
