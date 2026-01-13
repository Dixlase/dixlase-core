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

use App\Services\MailServerValidatorService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * アカウント認証の共通トレイト
 * 
 * メンバーとユーザーのアカウント認証処理で共通して使用される機能を提供します。
 * このトレイトを使用するコントローラーは、以下の抽象メソッドを実装する必要があります。
 */
trait AccountVerificationTrait
{
    /**
     * 認証完了通知クラスを取得（継承先で実装）
     * 
     * @return string|null 通知クラス名
     */
    abstract protected function getVerificationCompletedNotificationClass(): ?string;

    /**
     * 管理者通知クラスを取得（継承先で実装）
     * 
     * @return string|null 通知クラス名
     */
    abstract protected function getAdminVerifiedNotificationClass(): ?string;

    /**
     * 管理者メールアドレス設定キーを取得（継承先で実装）
     * 
     * @return string 設定キー名
     */
    abstract protected function getAdminEmailSettingKey(): string;

    /**
     * 通知メールアドレス設定キーを取得（継承先で実装）
     * 
     * @return string 設定キー名
     */
    abstract protected function getNotificationEmailSettingKey(): string;

    /**
     * 成功メッセージキーを取得（継承先で実装）
     * 
     * @return string 翻訳キー
     */
    abstract protected function getAccountVerificationSuccessKey(): string;

    /**
     * メール変更成功メッセージキーを取得（継承先で実装）
     * 
     * @return string 翻訳キー
     */
    abstract protected function getEmailChangeSuccessKey(): string;

    /**
     * 設定モデルクラスを取得（継承先で実装）
     * 
     * @return string 設定モデルクラス名
     */
    abstract protected function getSettingModelClass(): string;

    /**
     * ログイン後にメール認証が待機中の場合、認証処理を実行
     * 
     * @param mixed $user ユーザーモデル（Member または User）
     * @param \Illuminate\Http\Request $request リクエストオブジェクト
     * @return void
     */
    protected function processEmailVerificationIfPending($user, $request): void
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
        
        // ログインしたユーザーと認証待ちのユーザーが一致するかチェック
        if ($user->id !== $verificationData['member_id']) {
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
                $this->processEmailChange($user);
            } else {
                // 新規アカウントの認証
                $this->processAccountVerification($user);
            }
            
            // 認証完了後、セッションから削除
            session()->forget('email_verification_pending');
            
        } catch (\Exception $e) {
            Log::error('[Account Verification] Failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            session()->forget('email_verification_pending');
            session()->flash('error', __('auth.verification_failed'));
        }
    }

    /**
     * メールアドレス変更の認証処理
     * 
     * @param mixed $user
     * @return void
     */
    protected function processEmailChange($user): void
    {
        $user->email = $user->pending_email;
        $user->pending_email = null;
        $user->email_verified_at = now();
        $user->save();
        
        Log::info('[Account Verification] Email change verified', [
            'user_id' => $user->id,
            'new_email' => $user->email
        ]);
        
        session()->flash('success', __($this->getEmailChangeSuccessKey()));
    }

    /**
     * 新規アカウントの認証処理
     * 
     * @param mixed $user
     * @return void
     */
    protected function processAccountVerification($user): void
    {
        $user->markEmailAsVerified();
        
        Log::info('[Account Verification] Account verified', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);
        
        session()->flash('success', __($this->getAccountVerificationSuccessKey()));
        
        // メールサーバー設定済みの場合のみ通知を送信
        if (MailServerValidatorService::isMailServerTested()) {
            $this->sendVerificationNotifications($user);
        }
    }

    /**
     * 認証完了通知を送信
     * 
     * @param mixed $user
     * @return void
     */
    protected function sendVerificationNotifications($user): void
    {
        // ユーザー本人に認証完了メールを送信
        $notificationClass = $this->getVerificationCompletedNotificationClass();
        if ($notificationClass && class_exists($notificationClass)) {
            try {
                $user->notify(new $notificationClass());
                
                Log::info('[Account Verification] Notification sent to user', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
            } catch (\Exception $e) {
                Log::error('[Account Verification] Failed to send notification to user', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // 管理者に通知
        $adminNotificationClass = $this->getAdminVerifiedNotificationClass();
        if ($adminNotificationClass && class_exists($adminNotificationClass)) {
            try {
                $settingModel = $this->getSettingModelClass();
                $adminEmail = $settingModel::getValue($this->getAdminEmailSettingKey()) 
                    ?? $settingModel::getValue($this->getNotificationEmailSettingKey());
                
                if ($adminEmail) {
                    Notification::route('mail', $adminEmail)
                        ->notify(new $adminNotificationClass(
                            $user,
                            now()->format('Y-m-d H:i:s')
                        ));
                    
                    Log::info('[Account Verification] Notification sent to admin', [
                        'user_id' => $user->id,
                        'admin_email' => $adminEmail
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('[Account Verification] Failed to send notification to admin', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
