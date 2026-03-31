<?php

/**
 * This file is part of Dixlase.
 *
 * Copyright (C) 2026 exc-D inc.
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

use App\Helpers\LoginLockoutHelper;
use App\Models\SecuritySetting;
use Illuminate\Http\Request;

/**
 * @api プラグイン/テーマから直接DIで使用可能な安定APIです
 */
class AdminLoginLockoutService
{
    /**
     * ログイン試行制限が有効かどうかを確認
     */
    public function isLockoutEnabled(): bool
    {
        return LoginLockoutHelper::isLockoutEnabled();
    }

    /**
     * ロックアウト通知が有効かどうかを確認
     */
    public function isNotificationEnabled(): bool
    {
        return LoginLockoutHelper::isNotificationEnabled();
    }

    /**
     * 指定した識別子がロックアウトされているかを確認
     */
    public function isLockedOut(string $identifier): bool
    {
        $settings = LoginLockoutHelper::getLockoutSettings();
        if (! $settings['enabled']) {
            return false;
        }

        $failedAttempts = \App\Models\MemberLoginAttempt::getFailedAttemptsCount(
            $identifier,
            $settings['time_window']
        );

        return $failedAttempts >= $settings['max_attempts'];
    }

    /**
     * IPアドレスがロックアウトされているかを確認
     */
    public function isIpLockedOut(string $ipAddress): bool
    {
        $settings = LoginLockoutHelper::getLockoutSettings();
        if (! $settings['enabled']) {
            return false;
        }

        $failedAttempts = \App\Models\MemberLoginAttempt::getFailedAttemptsCountByIp(
            $ipAddress,
            $settings['time_window']
        );

        // IP用の最大試行回数を取得（セキュリティ設定から）
        $maxAttemptsForIp = SecuritySetting::get('login_attempt_max_attempts_ip', $settings['max_attempts'] * 2);

        return $failedAttempts >= $maxAttemptsForIp;
    }

    /**
     * ロックアウト解除までの残り時間（分）を取得
     */
    public function getLockoutRemainingMinutes(string $identifier): ?int
    {
        $settings = LoginLockoutHelper::getLockoutSettings();

        return LoginLockoutHelper::getLockoutRemainingMinutes($identifier, $settings['lockout_duration']);
    }

    /**
     * 成功したログイン後の処理
     */
    public function handleSuccessfulLogin(string $identifier): void
    {
        LoginLockoutHelper::clearFailedAttempts($identifier);
    }

    /**
     * 失敗したログイン後の処理
     */
    public function handleFailedLogin(Request $request, string $identifier, ?string $failureReason = null): array
    {
        return LoginLockoutHelper::recordAndCheckLockout($request, $identifier, false, failureReason: $failureReason);
    }

    /**
     * ロックアウト状態の詳細情報を取得
     */
    public function getLockoutStatusDetails(string $identifier, string $ipAddress): array
    {
        return LoginLockoutHelper::getLockoutStatusDetails($identifier, $ipAddress);
    }

    /**
     * ロックアウトエラーメッセージを生成
     */
    public function generateLockoutMessage(array $lockoutInfo): string
    {
        return LoginLockoutHelper::generateLockoutMessage($lockoutInfo, 'admin');
    }
}
