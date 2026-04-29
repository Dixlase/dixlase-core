<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * @api Stable API available for plugins/themes
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

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
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
     * メールアドレスでのログインをサポートするかどうか（継承先で実装）
     */
    abstract protected function supportsEmailLogin(): bool;

    /**
     * アカウント名でのログインをサポートするかどうか（継承先で実装）
     */
    abstract protected function supportsAccountNameLogin(): bool;

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
     * 委譲パターン: supportsEmailLogin() / supportsAccountNameLogin() の結果に基づいて検索
     *
     * @param  string  $login  ログイン入力値
     * @param  string  $userModelClass  ユーザーモデルクラス名
     * @return mixed ユーザーモデルまたはnull
     */
    protected function findUserByLogin(string $login, string $userModelClass)
    {
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL) !== false;

        if ($isEmail) {
            if (! $this->supportsEmailLogin()) {
                return;
            }

            return $userModelClass::where('email', $login)->first();
        }

        if ($this->supportsAccountNameLogin()) {
            return $userModelClass::where('account_name', $login)->first();
        }

    }
}
