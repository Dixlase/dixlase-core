<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
 * https://exc-d.com
 *
 * Dixlase is dual-licensed. You may use this file under either:
 *
 *   (a) the GNU Affero General Public License version 3 or later, as
 *       published by the Free Software Foundation, together with the
 *       Dixlase Plugin and Theme Exception (see
 *       LICENSE-EXCEPTIONS for full exception terms); or
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

namespace App\Helpers;

use App\Services\MailServerValidatorService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * @internal コア専用。プラグイン/テーマから参照しないこと
 */
class EmailVerificationHelper
{
    /**
     * メール認証ハッシュを生成
     *
     * @param  mixed  $user  ユーザーモデル
     * @return string ハッシュ値
     */
    public function generateVerificationHash($user): string
    {
        return sha1($user->getEmailForVerification());
    }

    /**
     * メール認証ハッシュを検証
     *
     * @param  mixed  $user  ユーザーモデル
     * @param  string  $hash  検証するハッシュ
     * @return bool 検証結果
     */
    public function verifyHash($user, string $hash): bool
    {
        return hash_equals((string) $hash, $this->generateVerificationHash($user));
    }

    /**
     * メール認証が必要かチェック
     *
     * @param  mixed  $user  ユーザーモデル
     * @return array ['needs_verification' => bool, 'is_email_change' => bool]
     */
    public function needsVerification($user): array
    {
        $isEmailChange = ! empty($user->pending_email);
        $needsVerification = ! $user->hasVerifiedEmail() || $isEmailChange;

        return [
            'needs_verification' => $needsVerification,
            'is_email_change' => $isEmailChange,
        ];
    }

    /**
     * メール認証を即座に処理（ログイン済みの場合）
     *
     * @param  mixed  $user  ユーザーモデル
     * @param  string  $context  コンテキスト（admin, user等）
     * @return array ['success' => bool, 'message' => string, 'redirect' => string]
     */
    public function processVerificationImmediately($user, string $context = 'admin'): array
    {
        try {
            if ($user->pending_email) {
                // メールアドレス変更の認証
                $oldEmail = $user->email;
                $user->email = $user->pending_email;
                $user->pending_email = null;
                $user->email_verified_at = now();
                $user->save();

                Log::info('[Email Verification] Email change verified immediately', [
                    'user_id' => $user->id,
                    'old_email' => $oldEmail,
                    'new_email' => $user->email,
                    'context' => $context,
                ]);

                return [
                    'success' => true,
                    'message' => __('account.email_verification_success'),
                    'redirect' => route("{$context}.profile"),
                ];
            } else {
                // 新規アカウントの認証
                $user->markEmailAsVerified();

                Log::info('[Email Verification] Account verified immediately', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'context' => $context,
                ]);

                // 認証完了通知を送信
                $this->sendVerificationNotifications($user, $context);

                return [
                    'success' => true,
                    'message' => __('account.account_verification_success'),
                    'redirect' => route("{$context}.dashboard"),
                ];
            }
        } catch (\Exception $e) {
            Log::error('[Email Verification] Verification failed (immediate)', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'context' => $context,
            ]);

            return [
                'success' => false,
                'message' => __('auth.verification_failed'),
                'redirect' => route("{$context}.profile"),
            ];
        }
    }

    /**
     * メール認証情報をセッションに保存（未ログイン時）
     *
     * @param  mixed  $user  ユーザーモデル
     * @param  string  $hash  認証ハッシュ
     * @param  int  $expiresMinutes  有効期限（分）
     * @return array セッションに保存されたデータ
     */
    public function storeVerificationInSession($user, string $hash, int $expiresMinutes = 30): array
    {
        $data = [
            'member_id' => $user->id,
            'hash' => $hash,
            'email' => $user->pending_email ?? $user->email,
            'is_email_change' => (bool) $user->pending_email,
            'expires_at' => now()->addMinutes($expiresMinutes)->timestamp,
        ];

        session(['email_verification_pending' => $data]);
        session()->save();

        Log::info('[Email Verification] Verification info stored in session', [
            'user_id' => $user->id,
            'is_email_change' => $data['is_email_change'],
            'expires_at' => date('Y-m-d H:i:s', $data['expires_at']),
        ]);

        return $data;
    }

    /**
     * セッションからメール認証情報を取得
     *
     * @return array|null 認証情報または null
     */
    public function getVerificationFromSession(): ?array
    {
        $data = session('email_verification_pending');

        if (! $data) {
            return null;
        }

        // 有効期限チェック
        if (isset($data['expires_at']) && now()->timestamp > $data['expires_at']) {
            session()->forget('email_verification_pending');
            Log::warning('[Email Verification] Session data expired', [
                'expired_at' => date('Y-m-d H:i:s', $data['expires_at']),
            ]);

            return null;
        }

        return $data;
    }

    /**
     * セッションからメール認証情報を削除
     */
    public function clearVerificationFromSession(): void
    {
        session()->forget('email_verification_pending');
    }

    /**
     * 認証完了通知を送信
     *
     * @param  mixed  $user  ユーザーモデル
     * @param  string  $context  コンテキスト（admin, user等）
     */
    protected function sendVerificationNotifications($user, string $context = 'admin'): void
    {
        // メールサーバー設定済みの場合のみ通知を送信
        if (! MailServerValidatorService::isMailServerTested()) {
            Log::info('[Email Verification] Mail server not configured, skipping notifications');

            return;
        }

        // ユーザー本人に認証完了メールを送信
        $this->sendUserNotification($user, $context);

        // 管理者に通知
        $this->sendAdminNotification($user, $context);
    }

    /**
     * ユーザー本人に認証完了通知を送信
     *
     * @param  mixed  $user  ユーザーモデル
     * @param  string  $context  コンテキスト
     */
    protected function sendUserNotification($user, string $context): void
    {
        try {
            $notificationClass = $this->getVerificationCompletedNotificationClass($context);

            if (class_exists($notificationClass)) {
                $user->notify(new $notificationClass());

                Log::info('[Email Verification] Verification completed notification sent to user', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'context' => $context,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[Email Verification] Failed to send verification completed notification to user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'context' => $context,
            ]);
        }
    }

    /**
     * 管理者に認証完了通知を送信
     *
     * @param  mixed  $user  ユーザーモデル
     * @param  string  $context  コンテキスト
     */
    protected function sendAdminNotification($user, string $context): void
    {
        try {
            $adminEmail = \App\Models\SiteSetting::getValue('system_admin_email')
                ?? \App\Models\SiteSetting::getValue('notification_email');

            if (! $adminEmail) {
                Log::info('[Email Verification] No admin email configured, skipping admin notification');

                return;
            }

            $notificationClass = $this->getAdminVerifiedNotificationClass($context);

            if (class_exists($notificationClass)) {
                Notification::route('mail', $adminEmail)
                    ->notify(new $notificationClass($user, now()->format('Y-m-d H:i:s')));

                Log::info('[Email Verification] Verification notification sent to admin', [
                    'user_id' => $user->id,
                    'admin_email' => $adminEmail,
                    'context' => $context,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[Email Verification] Failed to send verification notification to admin', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'context' => $context,
            ]);
        }
    }

    /**
     * コンテキストに応じた認証完了通知クラスを取得
     *
     * @param  string  $context  コンテキスト
     * @return string 通知クラス名
     */
    protected function getVerificationCompletedNotificationClass(string $context): string
    {
        return match ($context) {
            'admin' => \App\Notifications\MemberVerificationCompletedNotification::class,
            'user' => \App\Notifications\UserVerificationCompletedNotification::class,
            default => \App\Notifications\MemberVerificationCompletedNotification::class,
        };
    }

    /**
     * コンテキストに応じた管理者通知クラスを取得
     *
     * @param  string  $context  コンテキスト
     * @return string 通知クラス名
     */
    protected function getAdminVerifiedNotificationClass(string $context): string
    {
        return match ($context) {
            'admin' => \App\Notifications\AdminMemberVerifiedNotification::class,
            'user' => \App\Notifications\AdminUserVerifiedNotification::class,
            default => \App\Notifications\AdminMemberVerifiedNotification::class,
        };
    }

    /**
     * メール認証リンクのメッセージキーを取得
     *
     * @param  bool  $isEmailChange  メールアドレス変更かどうか
     * @return string メッセージキー
     */
    public function getLoginRequiredMessageKey(bool $isEmailChange): string
    {
        return $isEmailChange
            ? 'account.verify_email_change_login_required'
            : 'account.verify_email_login_required';
    }
}
