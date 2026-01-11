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

namespace App\Services;

use App\Services\MailServerValidatorService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * アカウント認証サービス
 * 
 * メールアドレスの所有確認とアカウント有効化を処理
 * 管理メンバーとユーザープラグインの両方で使用可能
 */
class AccountVerificationService
{
    /**
     * ログイン後にメール認証が待機中の場合、認証処理を実行
     * 
     * @param mixed $user ユーザーモデル（Member or DixlaseUsersUser）
     * @param array $config 設定配列
     *   - 'verification_completed_notification' => 認証完了通知クラス
     *   - 'admin_verified_notification' => 管理者通知クラス
     *   - 'admin_email_setting_key' => 管理者メールアドレスの設定キー
     *   - 'notification_email_setting_key' => 通知メールアドレスの設定キー
     *   - 'success_message_key' => 成功メッセージの翻訳キー
     *   - 'email_change_success_key' => メール変更成功メッセージの翻訳キー
     *   - 'setting_model_class' => 設定モデルクラス
     * @return void
     */
    public function processIfPending($user, array $config = [])
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
                $this->processEmailChange($user, $config);
            } else {
                // 新規アカウントの認証
                $this->processAccountVerification($user, $config);
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
     * @param array $config
     * @return void
     */
    protected function processEmailChange($user, array $config)
    {
        $user->email = $user->pending_email;
        $user->pending_email = null;
        $user->email_verified_at = now();
        $user->save();
        
        Log::info('[Account Verification] Email change verified', [
            'user_id' => $user->id,
            'new_email' => $user->email
        ]);
        
        $successKey = $config['email_change_success_key'] ?? 'admin/profile.email_verification_success';
        session()->flash('success', __($successKey));
    }

    /**
     * 新規アカウントの認証処理
     * 
     * @param mixed $user
     * @param array $config
     * @return void
     */
    protected function processAccountVerification($user, array $config)
    {
        $user->markEmailAsVerified();
        
        Log::info('[Account Verification] Account verified', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);
        
        $successKey = $config['success_message_key'] ?? 'admin/profile.account_verification_success';
        session()->flash('success', __($successKey));
        
        // メールサーバー設定済みの場合のみ通知を送信
        if (MailServerValidatorService::isMailServerTested()) {
            $this->sendVerificationNotifications($user, $config);
        }
    }

    /**
     * 認証完了通知を送信
     * 
     * @param mixed $user
     * @param array $config
     * @return void
     */
    protected function sendVerificationNotifications($user, array $config)
    {
        // ユーザー本人に認証完了メールを送信
        if (isset($config['verification_completed_notification'])) {
            try {
                $notificationClass = $config['verification_completed_notification'];
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
        if (isset($config['admin_verified_notification']) && isset($config['setting_model_class'])) {
            try {
                $settingModel = $config['setting_model_class'];
                $adminEmail = $settingModel::getValue($config['admin_email_setting_key'] ?? 'system_admin_email') 
                    ?? $settingModel::getValue($config['notification_email_setting_key'] ?? 'notification_email');
                
                if ($adminEmail) {
                    $notificationClass = $config['admin_verified_notification'];
                    Notification::route('mail', $adminEmail)
                        ->notify(new $notificationClass(
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
