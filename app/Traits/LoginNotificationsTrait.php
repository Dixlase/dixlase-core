<?php

/**
 * This file is part of MySoftware.
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
use App\Enums\LoginNotificationMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

trait LoginNotificationsTrait
{
    /**
     * ログイン情報を記録する
     */
    protected function recordLoginInfo(Model $user, Request $request): void
    {
        $user->last_login_ip = $request->ip();
        $user->last_login_ua = $request->userAgent();
        $user->last_login_at = now();
        $user->save();
    }

    /**
     * ログイン詳細データを準備する
     */
    protected function prepareLoginDetails(Request $request): array
    {
        return [
            'datetime' => now()->format('Y-m-d H:i:s'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }

    /**
     * メール送信が可能かチェックし、不可の場合はログを出力
     */
    protected function canSendNotification(Model $user, string $context = 'Login notification'): bool
    {
        if (!MailServerValidatorService::canSendMail()) {
            Log::info($context . ' skipped: ' . MailServerValidatorService::getMailDisabledReason(), [
                'user_id' => $user->id,
                'user_type' => get_class($user),
                'ip' => request()->ip()
            ]);
            return false;
        }
        return true;
    }

    /**
     * ユーザー通知を送信すべきかどうかを判定する
     * 
     * @param Model $user ユーザーモデル
     * @param string $ip 現在のIPアドレス
     * @param string $ua 現在のUser-Agent
     * @param callable $getGlobalSetting グローバル設定取得関数
     * @return bool
     */
    protected function shouldSendUserNotification(
        Model $user, 
        string $ip, 
        string $ua, 
        callable $getGlobalSetting
    ): bool {
        $globalSetting = $getGlobalSetting();
        $globalMode = LoginNotificationMode::tryFrom((int) $globalSetting);

        return match ($globalMode) {
            LoginNotificationMode::Disabled => false,
            LoginNotificationMode::Always => true,
            LoginNotificationMode::OnlyNewDevice => $this->isNewDevice($user, $ip, $ua),
            LoginNotificationMode::UseProfileSetting => $this->shouldSendBasedOnProfile($user, $ip, $ua),
            default => false,
        };
    }

    /**
     * 新しいデバイス/IPからのアクセスかどうかを判定
     */
    protected function isNewDevice(Model $user, string $ip, string $ua): bool
    {
        return $ip !== $user->last_login_ip || $ua !== $user->last_login_ua;
    }

    /**
     * プロフィール設定に基づいて通知を送信すべきかを判定
     */
    protected function shouldSendBasedOnProfile(Model $user, string $ip, string $ua): bool
    {
        // プロフィール設定の値をenumの値にマッピング
        $profileValue = $user->login_notification_mode ?? 1; // デフォルト: 無効
        $profileMode = $this->mapProfileValueToEnum($profileValue);
        
        return match ($profileMode) {
            LoginNotificationMode::Disabled => false,
            LoginNotificationMode::Always => true,
            LoginNotificationMode::OnlyNewDevice => $this->isNewDevice($user, $ip, $ua),
            default => false,
        };
    }

    /**
     * プロフィール設定の値をLoginNotificationMode enumにマッピング
     */
    protected function mapProfileValueToEnum(int $profileValue): LoginNotificationMode
    {
        return match ($profileValue) {
            1 => LoginNotificationMode::Disabled,      // プロフィール: 無効 → enum: Disabled(0)
            2 => LoginNotificationMode::Always,        // プロフィール: 常に有効 → enum: Always(3)
            3 => LoginNotificationMode::OnlyNewDevice, // プロフィール: 異なる端末/IP時のみ → enum: OnlyNewDevice(2)
            default => LoginNotificationMode::Disabled,
        };
    }

    /**
     * システム通知用の一時的なNotifiableオブジェクトを作成
     */
    protected function createSystemNotifiable(string $email, string $name = 'System Administrator'): object
    {
        return new class($email, $name) {
            public function __construct(public string $email, public string $name) {}
            public function routeNotificationForMail() { return $this->email; }
        };
    }

    /**
     * システム通知を送信する
     * 
     * @param array $loginDetails ログイン詳細
     * @param callable $getSystemEmail システムメールアドレス取得関数
     * @param callable $isSystemNotificationEnabled システム通知有効判定関数
     * @param string $notificationClass 通知クラス名
     */
    protected function sendSystemNotification(
        array $loginDetails,
        callable $getSystemEmail,
        callable $isSystemNotificationEnabled,
        string $notificationClass
    ): void {
        if (!$isSystemNotificationEnabled()) {
            return;
        }

        $adminEmail = $getSystemEmail();
        if (!$adminEmail) {
            Log::warning('System notification skipped: No admin email configured');
            return;
        }

        $systemNotifiable = $this->createSystemNotifiable($adminEmail);
        $systemNotifiable->notify(new $notificationClass($loginDetails, true));
    }
}
